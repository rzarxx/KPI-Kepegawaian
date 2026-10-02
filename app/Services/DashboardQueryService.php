<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeAttentionRule;
use App\Models\EmployeeEvaluation;
use App\Models\PerformancePeriod;
use App\Models\SubDivision;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardQueryService
{
    public function __construct(
        private readonly EmployeeQueryService $employees,
        private readonly EmployeeAttentionService $attention,
        private readonly OrganizationalScopeResolver $scopeResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function gather(User $user, array $filters): array
    {
        $query = $this->buildBaseQuery($user, $filters);
        $employeeIds = (clone $query)->pluck('employees.id');
        $canViewEvaluations = $user->can('evaluation.view');

        $evaluationQuery = $this->buildEvaluationQuery($employeeIds, $filters);
        $attentionEmployees = $this->resolveAttentionEmployees($user, $query, $canViewEvaluations);

        return [
            'metrics' => $this->resolveMetrics($query, $evaluationQuery, $attentionEmployees, $canViewEvaluations),
            'distributions' => $this->resolveDistributions($query),
            'trend' => $this->resolveTrend($evaluationQuery, $canViewEvaluations),
            'priorities' => $this->resolvePriorities($attentionEmployees),
            'activities' => $this->resolveActivities($evaluationQuery, $canViewEvaluations),
            'filterOptions' => $this->resolveFilterOptions($user, $canViewEvaluations),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildBaseQuery(User $user, array $filters): Builder
    {
        $query = $this->employees->visibleTo($user);

        foreach (['branch_id', 'division_id', 'sub_division_id'] as $column) {
            $query->when($filters[$column] ?? null, fn (Builder $q, $id) => $q->where(function (Builder $employee) use ($column, $id): void {
                $assignmentFilter = fn (Builder $assignment) => $assignment->where($column, $id);
                $employee->whereHas('currentAssignment', $assignmentFilter)
                    ->orWhere(fn (Builder $inactive) => $inactive->whereDoesntHave('currentAssignment')->whereHas('latestAssignment', $assignmentFilter));
            }));
        }

        return $query;
    }

    /**
     * @param  Collection<int, int>  $employeeIds
     * @param  array<string, mixed>  $filters
     */
    private function buildEvaluationQuery(Collection $employeeIds, array $filters): Builder
    {
        return EmployeeEvaluation::query()
            ->whereIn('employee_evaluations.employee_id', $employeeIds)
            ->whereIn('employee_evaluations.status', ['FINALIZED', 'CLOSED'])
            ->when($filters['period_id'] ?? null, fn (Builder $q, $id) => $q->where('employee_evaluations.period_id', $id));
    }

    private function resolveAttentionEmployees(User $user, Builder $query, bool $canViewEvaluations): Collection
    {
        $allowedRuleTypes = collect()
            ->when($user->can('employee_incident.view'), fn ($types) => $types->push('INCIDENT_SEVERITY_COUNT'))
            ->when($canViewEvaluations, fn ($types) => $types->push('EVALUATION_BELOW', 'ATTENDANCE_BELOW'));

        $attentionRules = EmployeeAttentionRule::query()
            ->where('is_active', true)
            ->whereIn('rule_type', $allowedRuleTypes)
            ->get();

        return (clone $query)->get()
            ->map(fn ($employee) => ['employee' => $employee, 'reasons' => $this->attention->reasons($employee, $attentionRules)])
            ->filter(fn ($item) => count($item['reasons']) > 0)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMetrics(Builder $query, Builder $evaluationQuery, Collection $attentionEmployees, bool $canViewEvaluations): array
    {
        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('current_status', 'ACTIVE')->count(),
            'attention' => $attentionEmployees->count(),
            'average' => $canViewEvaluations ? round((float) (clone $evaluationQuery)->avg('total_score'), 2) : 0,
        ];
    }

    /**
     * @return array<string, Collection>
     */
    private function resolveDistributions(Builder $query): array
    {
        $distributionBase = (clone $query)
            ->with(['currentAssignment.branch', 'currentAssignment.division', 'latestAssignment.branch', 'latestAssignment.division'])
            ->get();

        $statusLabels = [
            'ACTIVE' => 'Aktif',
            'PROBATION' => 'Masa Percobaan',
            'MUTATED' => 'Mutasi',
            'RESIGNED' => 'Resign',
            'TERMINATED' => 'Terminasi',
            'INACTIVE' => 'Tidak Aktif',
        ];

        return [
            'byDivision' => $distributionBase->groupBy(function (Employee $employee): string {
                $assignment = $employee->currentAssignment ?? $employee->latestAssignment;

                return $assignment?->division?->name ?? 'Tidak ada';
            })->map(fn ($group, $name) => ['name' => $name, 'value' => $group->count()])->sortByDesc('value')->values(),
            'byBranch' => $distributionBase->groupBy(function (Employee $employee): string {
                $assignment = $employee->currentAssignment ?? $employee->latestAssignment;

                return $assignment?->branch?->name ?? 'Tidak ada';
            })->map(fn ($group, $name) => ['name' => $name, 'value' => $group->count()])->sortByDesc('value')->values(),
            'byStatus' => $distributionBase->groupBy(fn (Employee $employee): string => $employee->current_status->value)
                ->map(fn ($group, $status) => ['name' => $statusLabels[$status] ?? $status, 'value' => $group->count()])
                ->sortByDesc('value')->values(),
        ];
    }

    /**
     * @return Collection|array<int, never>
     */
    private function resolveTrend(Builder $evaluationQuery, bool $canViewEvaluations): Collection|array
    {
        if (! $canViewEvaluations) {
            return [];
        }

        return (clone $evaluationQuery)
            ->join('performance_periods', 'employee_evaluations.period_id', '=', 'performance_periods.id')
            ->selectRaw('performance_periods.name as name, ROUND(AVG(employee_evaluations.total_score), 2) as score')
            ->groupBy('performance_periods.id', 'performance_periods.name', 'performance_periods.start_date')
            ->orderBy('performance_periods.start_date')
            ->get();
    }

    private function resolvePriorities(Collection $attentionEmployees): Collection
    {
        return $attentionEmployees->take(8)->map(fn ($item) => [
            'id' => $item['employee']->id,
            'name' => $item['employee']->full_name,
            'number' => $item['employee']->employee_number,
            'reasons' => $item['reasons'],
        ]);
    }

    private function resolveActivities(Builder $evaluationQuery, bool $canViewEvaluations): Collection
    {
        if (! $canViewEvaluations) {
            return collect();
        }

        return (clone $evaluationQuery)
            ->with(['employee', 'criterion'])
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(fn ($evaluation) => [
                'id' => $evaluation->id,
                'employee' => $evaluation->employee->full_name,
                'score' => $evaluation->total_score,
                'criteria' => $evaluation->criterion?->name,
                'date' => $evaluation->updated_at->toIso8601String(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilterOptions(User $user, bool $canViewEvaluations): array
    {
        $branches = $this->scopeResolver->scopeBranches($user, Branch::query()->where('is_active', true))
            ->orderBy('name')->get(['id', 'name']);

        $divisions = Division::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('branch_id')->orWhereIn('branch_id', $branches->pluck('id')))
            ->orderBy('name')->get(['id', 'branch_id', 'name']);

        return [
            'periods' => $canViewEvaluations
                ? PerformancePeriod::query()->orderByDesc('start_date')->get(['id', 'name'])
                : [],
            'branches' => $branches,
            'divisions' => $divisions,
            'subDivisions' => SubDivision::query()->where('is_active', true)
                ->whereIn('division_id', $divisions->pluck('id'))
                ->orderBy('name')->get(['id', 'division_id', 'name']),
        ];
    }
}

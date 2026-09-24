<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Division;
use App\Models\EmployeeAttentionRule;
use App\Models\EmployeeEvaluation;
use App\Models\PerformancePeriod;
use App\Models\SubDivision;
use App\Services\EmployeeAttentionService;
use App\Services\EmployeeQueryService;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, EmployeeQueryService $employees, EmployeeAttentionService $attention): Response
    {
        abort_unless($request->user()->can('employee.view'), 403);
        $filters = $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:performance_periods,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'sub_division_id' => ['nullable', 'integer', 'exists:sub_divisions,id'],
        ]);
        $query = $employees->visibleTo($request->user());
        foreach (['branch_id', 'division_id', 'sub_division_id'] as $column) {
            $query->when($filters[$column] ?? null, fn (Builder $q, $id) => $q->where(function (Builder $employee) use ($column, $id): void {
                $assignmentFilter = fn (Builder $assignment) => $assignment->where($column, $id);
                $employee->whereHas('currentAssignment', $assignmentFilter)
                    ->orWhere(fn (Builder $inactive) => $inactive->whereDoesntHave('currentAssignment')->whereHas('latestAssignment', $assignmentFilter));
            }));
        }

        $employeeIds = (clone $query)->pluck('employees.id');
        $canViewEvaluations = $request->user()->can('evaluation.view');
        $allowedRuleTypes = collect()
            ->when($request->user()->can('employee_incident.view'), fn ($types) => $types->push('INCIDENT_SEVERITY_COUNT'))
            ->when($canViewEvaluations, fn ($types) => $types->push('EVALUATION_BELOW', 'ATTENDANCE_BELOW'));
        $attentionRules = EmployeeAttentionRule::query()->where('is_active', true)->whereIn('rule_type', $allowedRuleTypes)->get();
        $evaluationQuery = EmployeeEvaluation::query()
            ->whereIn('employee_evaluations.employee_id', $employeeIds)
            ->whereIn('employee_evaluations.status', ['FINALIZED', 'CLOSED'])
            ->when($filters['period_id'] ?? null, fn (Builder $q, $id) => $q->where('employee_evaluations.period_id', $id));
        $latestEvaluations = $canViewEvaluations
            ? (clone $evaluationQuery)->with(['employee', 'criterion'])->latest('created_at')->limit(12)->get()
            : collect();
        $attentionEmployees = (clone $query)->get()
            ->map(fn ($employee) => ['employee' => $employee, 'reasons' => $attention->reasons($employee, $attentionRules)])
            ->filter(fn ($item) => count($item['reasons']) > 0)->values();

        $scopeResolver = app(OrganizationalScopeResolver::class);
        $branches = $scopeResolver->scopeBranches($request->user(), Branch::query()->where('is_active', true))->orderBy('name')->get(['id', 'name']);
        $divisions = Division::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('branch_id')->orWhereIn('branch_id', $branches->pluck('id')))
            ->orderBy('name')->get(['id', 'branch_id', 'name']);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total' => (clone $query)->count(),
                'active' => (clone $query)->where('current_status', 'ACTIVE')->count(),
                'attention' => $attentionEmployees->count(),
                'average' => $canViewEvaluations ? round((float) (clone $evaluationQuery)->avg('total_score'), 2) : 0,
            ],
            'trend' => $canViewEvaluations ? (clone $evaluationQuery)->join('performance_periods', 'employee_evaluations.period_id', '=', 'performance_periods.id')
                ->selectRaw('performance_periods.name as name, ROUND(AVG(employee_evaluations.total_score), 2) as score')
                ->groupBy('performance_periods.id', 'performance_periods.name', 'performance_periods.start_date')
                ->orderBy('performance_periods.start_date')->get() : [],
            'priorities' => $attentionEmployees->take(8)->map(fn ($item) => [
                'id' => $item['employee']->id,
                'name' => $item['employee']->full_name,
                'number' => $item['employee']->employee_number,
                'reasons' => $item['reasons'],
            ]),
            'activities' => $latestEvaluations->map(fn ($evaluation) => [
                'id' => $evaluation->id,
                'employee' => $evaluation->employee->full_name,
                'score' => $evaluation->total_score,
                'criteria' => $evaluation->criterion?->name,
                'date' => $evaluation->updated_at->toIso8601String(),
            ]),
            'filters' => $filters,
            'filterOptions' => [
                'periods' => $canViewEvaluations ? PerformancePeriod::query()->orderByDesc('start_date')->get(['id', 'name']) : [],
                'branches' => $branches,
                'divisions' => $divisions,
                'subDivisions' => SubDivision::query()->where('is_active', true)->whereIn('division_id', $divisions->pluck('id'))->orderBy('name')->get(['id', 'division_id', 'name']),
            ],
        ]);
    }
}

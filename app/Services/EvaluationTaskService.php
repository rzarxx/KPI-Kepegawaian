<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\PerformancePeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EvaluationTaskService
{
    public function __construct(private readonly EmployeeQueryService $employees) {}

    /** @return array<string, mixed> */
    public function gather(User $user, ?int $periodId = null): array
    {
        $periods = PerformancePeriod::query()
            ->where('is_active', true)
            ->where('status', 'ACTIVE')
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'start_date', 'end_date']);
        $period = $periodId === null ? $periods->first() : $periods->firstWhere('id', $periodId);

        $employees = $this->employees->visibleTo($user)
            ->whereIn('current_status', ['ACTIVE', 'PROBATION'])
            ->whereHas('currentAssignment', fn (Builder $query) => $query->whereNotNull('sub_division_id'))
            ->with(['currentAssignment.branch', 'currentAssignment.division', 'currentAssignment.subDivision'])
            ->orderBy('full_name')
            ->get();

        $evaluations = $period === null
            ? collect()
            : EmployeeEvaluation::query()
                ->where('period_id', $period->id)
                ->where('evaluation_type', EmployeeEvaluation::TYPE_SUPERVISOR)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->with(['evaluator:id,name', 'period:id,status,is_active'])
                ->latest('id')
                ->get()
                ->unique('employee_id')
                ->keyBy('employee_id');

        $tasks = $employees->map(function (Employee $employee) use ($user, $evaluations, $period): array {
            $evaluation = $evaluations->get($employee->id);
            $status = match ($evaluation?->status) {
                null => 'NOT_STARTED',
                'DRAFT' => 'DRAFT',
                default => 'COMPLETED',
            };

            return [
                'employee' => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                ],
                'organization' => [
                    'branch' => $employee->currentAssignment?->branch?->name,
                    'division' => $employee->currentAssignment?->division?->name,
                    'sub_division' => $employee->currentAssignment?->subDivision?->name,
                ],
                'evaluation_id' => $evaluation?->id,
                'status' => $status,
                'score' => $evaluation?->total_score,
                'evaluator' => $evaluation?->evaluator?->name,
                'can_evaluate' => $period !== null
                    && $user->can('evaluate', $employee)
                    && ($evaluation === null || ($evaluation->status === 'DRAFT' && $evaluation->evaluator_id === $user->id)),
                'can_reopen' => $evaluation !== null && $user->can('reopen', $evaluation),
            ];
        })->values();

        return [
            'periods' => $periods->map(fn (PerformancePeriod $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'start_date' => $item->start_date->toDateString(),
                'end_date' => $item->end_date->toDateString(),
            ])->values(),
            'activePeriod' => $period ? [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
            ] : null,
            'tasks' => $tasks,
            'summary' => [
                'total' => $tasks->count(),
                'not_started' => $tasks->where('status', 'NOT_STARTED')->count(),
                'draft' => $tasks->where('status', 'DRAFT')->count(),
                'completed' => $tasks->where('status', 'COMPLETED')->count(),
                'completion_percentage' => $tasks->count() === 0
                    ? 0
                    : round($tasks->where('status', 'COMPLETED')->count() / $tasks->count() * 100, 1),
            ],
            'needsPlacement' => $this->needsPlacementCount($user),
            'canEvaluate' => $user->hasRole('Sub Division Head'),
            'canConfigure' => $user->can('organization.manage'),
        ];
    }

    private function needsPlacementCount(User $user): int
    {
        if (! $user->hasAnyRole(['Super Admin', 'HR Admin', 'HR Manager'])) {
            return 0;
        }

        return $this->employees->visibleTo($user)
            ->whereIn('current_status', ['ACTIVE', 'PROBATION'])
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('currentAssignment')
                    ->orWhereHas('currentAssignment', fn (Builder $assignment) => $assignment->whereNull('sub_division_id'));
            })
            ->count();
    }
}

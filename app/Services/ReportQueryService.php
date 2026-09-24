<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReportQueryService
{
    public function query(User $user, array $filters, ?array $scopeSnapshot = null): Builder
    {
        $query = app(EmployeeQueryService::class)->visibleTo($user)
            ->with([
                'currentAssignment.branch', 'currentAssignment.division', 'currentAssignment.subDivision', 'currentAssignment.position',
                'latestAssignment.branch', 'latestAssignment.division', 'latestAssignment.subDivision', 'latestAssignment.position',
                'evaluations' => fn ($evaluation) => $evaluation
                    ->with(['scores.component', 'criterion', 'evaluator', 'period'])
                    ->when($filters['criteria_id'] ?? null, fn ($nested, $id) => $nested->where('criteria_id', $id))
                    ->when($filters['start_date'] ?? null, fn ($nested, $date) => $nested->whereDate('created_at', '>=', $date))
                    ->when($filters['end_date'] ?? null, fn ($nested, $date) => $nested->whereDate('created_at', '<=', $date))
                    ->latest('created_at'),
            ]);

        if ($scopeSnapshot && ! ($scopeSnapshot['global'] ?? false)) {
            $scopes = collect($scopeSnapshot['scopes'] ?? []);
            $query->where(function (Builder $employees) use ($scopes): void {
                foreach ($scopes as $scope) {
                    $employees->orWhere(function (Builder $employee) use ($scope): void {
                        $assignmentFilter = fn (Builder $assignment) => $assignment
                            ->where('branch_id', $scope['branch_id'])
                            ->when($scope['division_id'] ?? null, fn (Builder $q, $id) => $q->where('division_id', $id))
                            ->when($scope['sub_division_id'] ?? null, fn (Builder $q, $id) => $q->where('sub_division_id', $id));
                        $employee->whereHas('currentAssignment', $assignmentFilter)
                            ->orWhere(fn (Builder $inactive) => $inactive->whereDoesntHave('currentAssignment')->whereHas('latestAssignment', $assignmentFilter));
                    });
                }
            });
        }

        $this->applyOrganization($query, $filters);

        $query->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('current_status', $status));
        $query->when($filters['criteria_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('evaluations', fn (Builder $evaluation) => $evaluation->where('criteria_id', $id)));
        $query->when($filters['problem_status'] ?? null, fn (Builder $q, $status) => $q->whereHas('incidents', fn (Builder $incident) => $incident->where('status', $status)));
        if (($filters['start_date'] ?? null) || ($filters['end_date'] ?? null)) {
            $query->whereHas('evaluations', fn (Builder $evaluation) => $evaluation
                ->when($filters['start_date'] ?? null, fn (Builder $nested, $date) => $nested->whereDate('created_at', '>=', $date))
                ->when($filters['end_date'] ?? null, fn (Builder $nested, $date) => $nested->whereDate('created_at', '<=', $date)));
        }

        return $query->orderBy('full_name')->orderBy('employees.id');
    }

    private function applyOrganization(Builder $query, array $filters): void
    {
        foreach (['branch_id', 'division_id', 'sub_division_id'] as $column) {
            if (! empty($filters[$column])) {
                $query->where(function (Builder $employee) use ($column, $filters): void {
                    $assignmentFilter = fn (Builder $assignment) => $assignment->where($column, $filters[$column]);
                    $employee->whereHas('currentAssignment', $assignmentFilter)
                        ->orWhere(fn (Builder $inactive) => $inactive->whereDoesntHave('currentAssignment')->whereHas('latestAssignment', $assignmentFilter));
                });
            }
        }
    }
}

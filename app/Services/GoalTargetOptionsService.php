<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GoalTargetOptionsService
{
    public function __construct(
        private readonly OrganizationalScopeResolver $scopeResolver,
        private readonly EmployeeQueryService $employeeQuery,
    ) {}

    /**
     * @return array{
     *     allowed_levels: list<string>,
     *     branches: list<array{id: int, label: string}>,
     *     divisions: list<array{id: int, label: string, branch_id: int}>,
     *     employees: list<array{id: int, label: string, branch_id: int, division_id: int|null}>
     * }
     */
    public function for(User $user): array
    {
        $hasGlobalScope = $this->scopeResolver->allowedBranchIds($user) === null;
        $scopes = $hasGlobalScope ? collect() : $this->scopeResolver->effectiveScopes($user);

        $branches = Branch::query()
            ->where('is_active', true)
            ->when(! $hasGlobalScope, function (Builder $query) use ($scopes): void {
                $branchIds = $scopes
                    ->filter(fn ($scope): bool => $scope->branch_id !== null
                        && $scope->division_id === null
                        && $scope->sub_division_id === null)
                    ->pluck('branch_id')
                    ->unique();

                $query->whereKey($branchIds);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'label' => $branch->name,
            ])
            ->values();

        $divisions = Division::query()
            ->where('is_active', true)
            ->whereHas('branch', fn (Builder $query) => $query->where('is_active', true))
            ->when(! $hasGlobalScope, function (Builder $query) use ($scopes): void {
                $eligibleScopes = $scopes
                    ->filter(fn ($scope): bool => $scope->branch_id !== null && $scope->sub_division_id === null)
                    ->values();

                if ($eligibleScopes->isEmpty()) {
                    $query->whereKey([]);

                    return;
                }

                $query->where(function (Builder $scopeQuery) use ($eligibleScopes): void {
                    foreach ($eligibleScopes as $scope) {
                        $scopeQuery->orWhere(function (Builder $divisionQuery) use ($scope): void {
                            $divisionQuery->where('branch_id', $scope->branch_id);

                            if ($scope->division_id !== null) {
                                $divisionQuery->whereKey($scope->division_id);
                            }
                        });
                    }
                });
            })
            ->with('branch:id,name')
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name'])
            ->map(fn (Division $division): array => [
                'id' => $division->id,
                'label' => $division->name.' — '.$division->branch->name,
                'branch_id' => $division->branch_id,
            ])
            ->values();

        $employees = $this->employeeQuery
            ->visibleTo($user)
            ->whereIn('current_status', [EmployeeStatus::Active->value, EmployeeStatus::Probation->value])
            ->whereHas('currentAssignment')
            ->with(['currentAssignment.branch:id,name', 'currentAssignment.division:id,name'])
            ->orderBy('full_name')
            ->get(['id', 'employee_number', 'full_name'])
            ->map(function (Employee $employee): array {
                $assignment = $employee->currentAssignment;
                $organization = collect([
                    $assignment->branch?->name,
                    $assignment->division?->name,
                ])->filter()->join(' / ');

                return [
                    'id' => $employee->id,
                    'label' => $employee->full_name.' ('.$employee->employee_number.')'.($organization !== '' ? ' — '.$organization : ''),
                    'branch_id' => $assignment->branch_id,
                    'division_id' => $assignment->division_id,
                ];
            })
            ->values();

        $allowedLevels = collect([
            'COMPANY' => $hasGlobalScope,
            'BRANCH' => $branches->isNotEmpty(),
            'DIVISION' => $divisions->isNotEmpty(),
            'INDIVIDUAL' => $employees->isNotEmpty(),
        ])->filter()->keys()->values()->all();

        return [
            'allowed_levels' => $allowedLevels,
            'branches' => $branches->all(),
            'divisions' => $divisions->all(),
            'employees' => $employees->all(),
        ];
    }
}

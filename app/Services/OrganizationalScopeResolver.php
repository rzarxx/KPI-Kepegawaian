<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CalibrationSession;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Goal;
use App\Models\OrganizationalScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrganizationalScopeResolver
{
    /**
     * Apply the authenticated user's allowed branch set to a query.
     * A null result from allowedBranchIds means the user has an organization-wide scope.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeBranches(User $user, Builder $query, string $column = 'id'): Builder
    {
        $branchIds = $this->allowedBranchIds($user);

        return $branchIds === null ? $query : $query->whereIn($column, $branchIds);
    }

    /**
     * @return list<int>|null Null means organization-wide access.
     */
    public function allowedBranchIds(User $user): ?array
    {
        if ($user->hasRole('Super Admin')) {
            return null;
        }

        $scopes = $this->effectiveScopes($user);

        if ($scopes->contains(fn ($scope): bool => $scope->branch_id === null
            && $scope->division_id === null
            && $scope->sub_division_id === null)) {
            return null;
        }

        return $scopes->pluck('branch_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    public function allows(
        User $user,
        ?int $branchId,
        ?int $divisionId = null,
        ?int $subDivisionId = null,
    ): bool {
        if ($this->allowedBranchIds($user) === null) {
            return true;
        }

        if ($branchId === null) {
            return false;
        }

        return $this->effectiveScopes($user)
            ->contains(function ($scope) use ($branchId, $divisionId, $subDivisionId): bool {
                return ($scope->branch_id === null || $scope->branch_id === $branchId)
                    && ($scope->division_id === null || $scope->division_id === $divisionId)
                    && ($scope->sub_division_id === null || $scope->sub_division_id === $subDivisionId);
            });
    }

    public function allowsAssignment(User $user, EmployeeAssignment $assignment): bool
    {
        return $this->allows(
            $user,
            $assignment->branch_id,
            $assignment->division_id,
            $assignment->sub_division_id,
        );
    }

    public function allowsEmployee(User $user, Employee $employee): bool
    {
        $assignment = $employee->currentAssignment ?? $employee->latestAssignment;

        return $assignment !== null && $this->allowsAssignment($user, $assignment);
    }

    public function allowsGoal(User $user, Goal $goal): bool
    {
        if ($this->allowedBranchIds($user) === null) {
            return true;
        }

        $target = $goal->goalable;

        if ($target instanceof Branch) {
            return $this->allows($user, $target->id);
        }

        if ($target instanceof Division) {
            return $this->allows($user, $target->branch_id, $target->id);
        }

        return $target instanceof Employee && $this->allowsEmployee($user, $target);
    }

    public function allowsCalibrationSession(User $user, CalibrationSession $session): bool
    {
        if ($this->allowedBranchIds($user) === null) {
            return true;
        }

        if ($session->scope_type === 'BRANCH') {
            return $this->allows($user, $session->scope_id);
        }

        $division = $session->scope_type === 'DIVISION'
            ? Division::query()->find($session->scope_id)
            : null;

        return $division !== null && $this->allows($user, $division->branch_id, $division->id);
    }

    public function calibrationIncludesAssignment(CalibrationSession $session, EmployeeAssignment $assignment): bool
    {
        return match ($session->scope_type) {
            'BRANCH' => (int) $session->scope_id === (int) $assignment->branch_id,
            'DIVISION' => (int) $session->scope_id === (int) $assignment->division_id,
            'ALL', null => true,
            default => false,
        };
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeAssignments(User $user, Builder $query): Builder
    {
        if ($this->allowedBranchIds($user) === null) {
            return $query;
        }

        return $query->where(function (Builder $assignmentQuery) use ($user): void {
            foreach ($this->effectiveScopes($user) as $scope) {
                $assignmentQuery->orWhere(function (Builder $scopeQuery) use ($scope): void {
                    $scopeQuery->where('branch_id', $scope->branch_id);

                    if ($scope->division_id !== null) {
                        $scopeQuery->where('division_id', $scope->division_id);
                    }

                    if ($scope->sub_division_id !== null) {
                        $scopeQuery->where('sub_division_id', $scope->sub_division_id);
                    }
                });
            }
        });
    }

    /**
     * @return Builder<Goal>
     */
    public function scopeGoals(User $user, Builder $query): Builder
    {
        if ($this->allowedBranchIds($user) === null) {
            return $query;
        }

        $branchIds = $this->allowedBranchIds($user) ?? [];
        $divisionIds = Division::query()
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->filter(fn (Division $division): bool => $this->allows($user, $division->branch_id, $division->id))
            ->pluck('id');
        $employeeIds = $this->scopeAssignments($user, EmployeeAssignment::query()->whereNull('end_date'))
            ->pluck('employee_id');

        return $query->where(function (Builder $goalQuery) use ($branchIds, $divisionIds, $employeeIds): void {
            $goalQuery->where(function (Builder $branchQuery) use ($branchIds): void {
                $branchQuery->where('goalable_type', Branch::class)->whereIn('goalable_id', $branchIds);
            })->orWhere(function (Builder $divisionQuery) use ($divisionIds): void {
                $divisionQuery->where('goalable_type', Division::class)->whereIn('goalable_id', $divisionIds);
            })->orWhere(function (Builder $employeeQuery) use ($employeeIds): void {
                $employeeQuery->where('goalable_type', Employee::class)->whereIn('goalable_id', $employeeIds);
            });
        });
    }

    /** @return Collection<int, OrganizationalScope> */
    public function effectiveScopes(User $user): Collection
    {
        $query = $user->organizationalScopes()->where('is_active', true);

        if ($user->hasRole('Sub Division Head')) {
            $query->whereNotNull('branch_id')->whereNotNull('division_id')->whereNotNull('sub_division_id');
        } elseif ($user->hasRole('Division Head')) {
            $query->whereNotNull('branch_id')->whereNotNull('division_id')->whereNull('sub_division_id');
        } elseif ($user->hasRole('Branch Head')) {
            $query->whereNotNull('branch_id')->whereNull('division_id')->whereNull('sub_division_id');
        }

        return $query->get(['branch_id', 'division_id', 'sub_division_id']);
    }
}

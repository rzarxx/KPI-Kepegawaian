<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EmployeeQueryService
{
    public function visibleTo(User $user): Builder
    {
        $query = Employee::query();
        $scopes = $user->organizationalScopes()->where('is_active', true)->get();
        if (app(OrganizationalScopeResolver::class)->allowedBranchIds($user) === null) {
            return $query;
        }

        return $query->where(function (Builder $employeeQuery) use ($scopes): void {
            foreach ($scopes as $scope) {
                $employeeQuery->orWhere(function (Builder $query) use ($scope): void {
                    $scopeAssignment = fn (Builder $assignmentQuery) => $assignmentQuery->where('branch_id', $scope->branch_id)->when($scope->division_id, fn (Builder $nested) => $nested->where('division_id', $scope->division_id))->when($scope->sub_division_id, fn (Builder $nested) => $nested->where('sub_division_id', $scope->sub_division_id));
                    $query->whereHas('currentAssignment', $scopeAssignment)->orWhere(fn (Builder $inactive) => $inactive->whereDoesntHave('currentAssignment')->whereHas('latestAssignment', $scopeAssignment));
                });
            }
        });
    }
}

<?php

namespace App\Services;

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

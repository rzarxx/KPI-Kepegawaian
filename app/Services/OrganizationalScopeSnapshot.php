<?php

namespace App\Services;

use App\Models\User;

class OrganizationalScopeSnapshot
{
    public function capture(User $user): array
    {
        $global = app(OrganizationalScopeResolver::class)->allowedBranchIds($user) === null;

        return [
            'global' => $global,
            'scopes' => $global ? [] : $user->organizationalScopes()->where('is_active', true)
                ->get(['branch_id', 'division_id', 'sub_division_id', 'scope_type'])
                ->map(fn ($scope) => $scope->only(['branch_id', 'division_id', 'sub_division_id', 'scope_type']))
                ->values()->all(),
        ];
    }
}

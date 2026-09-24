<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;
use App\Services\OrganizationalScopeResolver;

class DivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view');
    }

    public function view(User $user, Division $division): bool
    {
        return $user->can('organization.view')
            && app(OrganizationalScopeResolver::class)->allows($user, $division->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, Division $division): bool
    {
        return $user->can('organization.manage')
            && app(OrganizationalScopeResolver::class)->allows($user, $division->branch_id);
    }
}

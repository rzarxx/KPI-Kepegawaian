<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Services\OrganizationalScopeResolver;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('organization.view')
            && app(OrganizationalScopeResolver::class)->allows($user, $branch->id);
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('organization.manage')
            && app(OrganizationalScopeResolver::class)->allows($user, $branch->id);
    }
}

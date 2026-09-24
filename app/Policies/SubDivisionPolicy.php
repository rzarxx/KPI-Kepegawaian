<?php

namespace App\Policies;

use App\Models\SubDivision;
use App\Models\User;
use App\Services\OrganizationalScopeResolver;

class SubDivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view');
    }

    public function view(User $user, SubDivision $subDivision): bool
    {
        return $user->can('organization.view')
            && app(OrganizationalScopeResolver::class)->allows(
                $user,
                $subDivision->division->branch_id,
                $subDivision->division_id,
                $subDivision->id,
            );
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, SubDivision $subDivision): bool
    {
        return $user->can('organization.manage')
            && $this->view($user, $subDivision);
    }
}

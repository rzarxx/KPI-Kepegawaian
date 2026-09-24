<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('organization.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('organization.manage');
    }
}

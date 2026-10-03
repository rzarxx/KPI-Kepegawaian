<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('goal.view');
    }

    public function view(User $user, Goal $goal): bool
    {
        return $user->can('goal.view');
    }

    public function create(User $user): bool
    {
        return $user->can('goal.create');
    }

    public function update(User $user, Goal $goal): bool
    {
        return $user->can('goal.update') && in_array($goal->status, ['DRAFT', 'ACTIVE'], true);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $user->can('goal.update') && $goal->status === 'DRAFT';
    }
}

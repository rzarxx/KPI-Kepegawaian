<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;
use App\Services\OrganizationalScopeResolver;

class GoalPolicy
{
    public function __construct(private readonly OrganizationalScopeResolver $scopeResolver) {}

    public function viewAny(User $user): bool
    {
        return $user->can('goal.view');
    }

    public function view(User $user, Goal $goal): bool
    {
        return $user->can('goal.view') && $this->scopeResolver->allowsGoal($user, $goal);
    }

    public function create(User $user): bool
    {
        return $user->can('goal.create');
    }

    public function update(User $user, Goal $goal): bool
    {
        return $user->can('goal.update')
            && $this->scopeResolver->allowsGoal($user, $goal)
            && in_array($goal->status, ['DRAFT', 'ACTIVE'], true);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $user->can('goal.update')
            && $this->scopeResolver->allowsGoal($user, $goal)
            && $goal->status === 'DRAFT';
    }
}

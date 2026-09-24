<?php

namespace App\Policies;

use App\Models\PerformancePeriod;
use App\Models\User;

class PerformancePeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('evaluation.view');
    }

    public function view(User $user, PerformancePeriod $period): bool
    {
        return $user->can('evaluation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, PerformancePeriod $period): bool
    {
        return $user->can('organization.manage') && $period->status === 'DRAFT';
    }

    public function transition(User $user, PerformancePeriod $period): bool
    {
        return $user->can('organization.manage') && $period->status !== 'CLOSED';
    }
}

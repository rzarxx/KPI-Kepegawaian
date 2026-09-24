<?php

namespace App\Policies;

use App\Models\EvaluationComponent;
use App\Models\User;

class EvaluationComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('evaluation.view');
    }

    public function view(User $user, EvaluationComponent $component): bool
    {
        return $user->can('evaluation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, EvaluationComponent $component): bool
    {
        return $user->can('organization.manage');
    }
}

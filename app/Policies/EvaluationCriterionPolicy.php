<?php

namespace App\Policies;

use App\Models\EvaluationCriterion;
use App\Models\User;

class EvaluationCriterionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('evaluation.view');
    }

    public function view(User $user, EvaluationCriterion $criterion): bool
    {
        return $user->can('evaluation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, EvaluationCriterion $criterion): bool
    {
        return $user->can('organization.manage');
    }
}

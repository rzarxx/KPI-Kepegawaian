<?php

namespace App\Policies;

use App\Models\EmployeeEvaluation;
use App\Models\User;

class EmployeeEvaluationPolicy
{
    public function view(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->can('evaluation.view') && $user->can('view', $evaluation->employee);
    }

    public function create(User $user): bool
    {
        return $user->can('evaluation.create');
    }

    public function update(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->can('evaluation.update') && $evaluation->status === 'DRAFT' && $this->view($user, $evaluation);
    }

    public function finalize(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->can('evaluation.finalize') && $evaluation->status === 'APPROVED' && $this->view($user, $evaluation);
    }

    public function submit(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->can('evaluation.submit') && $evaluation->status === 'DRAFT' && $this->view($user, $evaluation);
    }

    public function approve(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->can('evaluation.approve') && $evaluation->status === 'SUBMITTED' && $this->view($user, $evaluation);
    }

    public function close(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $user->can('evaluation.finalize') && $evaluation->status === 'FINALIZED' && $this->view($user, $evaluation);
    }
}

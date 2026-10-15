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
        return $evaluation->isSupervisorEvaluation()
            && $user->can('evaluation.update')
            && $evaluation->evaluator_id === $user->id
            && $evaluation->status === 'DRAFT'
            && $user->can('evaluate', $evaluation->employee);
    }

    public function complete(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $this->update($user, $evaluation);
    }

    public function reopen(User $user, EmployeeEvaluation $evaluation): bool
    {
        return $evaluation->isSupervisorEvaluation()
            && $user->can('evaluation.reopen')
            && $user->hasAnyRole(['Super Admin', 'HR Admin', 'HR Manager'])
            && in_array($evaluation->status, ['FINALIZED', 'CLOSED'], true)
            && $evaluation->period?->status === 'ACTIVE'
            && $evaluation->period?->is_active
            && $this->view($user, $evaluation);
    }

    /** @deprecated Workflow persetujuan berlapis tidak digunakan lagi. */
    public function submit(User $user, EmployeeEvaluation $evaluation): bool
    {
        return false;
    }

    /** @deprecated Workflow persetujuan berlapis tidak digunakan lagi. */
    public function approve(User $user, EmployeeEvaluation $evaluation): bool
    {
        return false;
    }

    /** @deprecated Gunakan complete. */
    public function finalize(User $user, EmployeeEvaluation $evaluation): bool
    {
        return false;
    }

    /** @deprecated Workflow persetujuan berlapis tidak digunakan lagi. */
    public function close(User $user, EmployeeEvaluation $evaluation): bool
    {
        return false;
    }
}

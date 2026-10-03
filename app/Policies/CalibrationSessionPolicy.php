<?php

namespace App\Policies;

use App\Models\CalibrationSession;
use App\Models\User;

class CalibrationSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('calibration.view');
    }

    public function view(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.view');
    }

    public function create(User $user): bool
    {
        return $user->can('calibration.manage');
    }

    public function update(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage') && $session->isEditable();
    }

    public function finalize(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage') && $session->status === 'IN_REVIEW';
    }

    public function apply(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage') && $session->status === 'FINALIZED';
    }
}

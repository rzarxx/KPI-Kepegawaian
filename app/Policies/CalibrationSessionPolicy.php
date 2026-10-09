<?php

namespace App\Policies;

use App\Models\CalibrationSession;
use App\Models\User;
use App\Services\OrganizationalScopeResolver;

class CalibrationSessionPolicy
{
    public function __construct(private readonly OrganizationalScopeResolver $scopeResolver) {}

    public function viewAny(User $user): bool
    {
        return $user->can('calibration.view');
    }

    public function view(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.view') && $this->scopeResolver->allowsCalibrationSession($user, $session);
    }

    public function create(User $user): bool
    {
        return $user->can('calibration.manage');
    }

    public function update(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage')
            && $this->scopeResolver->allowsCalibrationSession($user, $session)
            && $session->isEditable();
    }

    public function finalize(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage')
            && $this->scopeResolver->allowsCalibrationSession($user, $session)
            && $session->status === 'IN_REVIEW';
    }

    public function apply(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage')
            && $this->scopeResolver->allowsCalibrationSession($user, $session)
            && $session->status === 'FINALIZED';
    }

    public function delete(User $user, CalibrationSession $session): bool
    {
        return $user->can('calibration.manage')
            && $this->scopeResolver->allowsCalibrationSession($user, $session)
            && $session->status === 'DRAFT';
    }
}

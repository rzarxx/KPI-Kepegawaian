<?php

namespace App\Policies;

use App\Models\EmployeeIncident;
use App\Models\User;

class EmployeeIncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee_incident.view');
    }

    public function view(User $user, EmployeeIncident $incident): bool
    {
        return $user->can('employee_incident.view') && $user->can('view', $incident->employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employee_incident.create');
    }

    public function update(User $user, EmployeeIncident $incident): bool
    {
        return $user->can('employee_incident.update')
            && $user->can('view', $incident->employee)
            && in_array($incident->status, ['OPEN', 'UNDER_REVIEW'], true);
    }

    public function resolve(User $user, EmployeeIncident $incident): bool
    {
        return $user->can('employee_incident.resolve')
            && $user->can('view', $incident->employee)
            && in_array($incident->status, ['OPEN', 'UNDER_REVIEW'], true);
    }

    public function transition(User $user, EmployeeIncident $incident): bool
    {
        return $user->can('employee_incident.resolve')
            && $user->can('view', $incident->employee)
            && $incident->status !== 'CLOSED';
    }
}

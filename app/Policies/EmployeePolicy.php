<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\OrganizationalScopeResolver;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        $assignment = $employee->currentAssignment ?? $employee->latestAssignment;

        return $user->can('employee.view') && $assignment !== null && app(OrganizationalScopeResolver::class)->allows($user, $assignment->branch_id, $assignment->division_id, $assignment->sub_division_id);
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function evaluate(User $user, Employee $employee): bool
    {
        return $user->can('evaluation.create') && $this->view($user, $employee);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employee.update') && $this->view($user, $employee);
    }

    public function transfer(User $user, Employee $employee): bool
    {
        return $user->can('employee.transfer') && $this->view($user, $employee);
    }

    public function changeStatus(User $user, Employee $employee): bool
    {
        return $user->can('employee.change_status') && $this->view($user, $employee);
    }

    public function rehire(User $user, Employee $employee): bool
    {
        return $user->can('employee.change_status')
            && $this->view($user, $employee)
            && in_array($employee->current_status->value, ['RESIGNED', 'TERMINATED', 'INACTIVE'], true);
    }
}

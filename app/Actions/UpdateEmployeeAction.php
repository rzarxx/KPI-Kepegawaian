<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;

class UpdateEmployeeAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, Employee $employee, array $data): Employee
    {
        $before = $employee->only(array_keys($data));
        $employee->update($data);
        $this->audit->log('employee.update', $actor, $employee, $before, $employee->fresh()->only(array_keys($data)));

        return $employee;
    }
}

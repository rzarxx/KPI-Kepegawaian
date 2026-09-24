<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ChangeEmployeeStatusAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, Employee $employee, string $status, string $effectiveDate, ?string $reason): void
    {
        DB::transaction(function () use ($actor, $employee, $status, $effectiveDate, $reason) {
            $previous = $employee->current_status->value;
            $employee->currentAssignment?->update(['end_date' => $effectiveDate]);
            $employee->update(['current_status' => $status, 'exit_date' => $effectiveDate, 'exit_reason' => $reason]);
            $employee->statusHistories()->create(['previous_status' => $previous, 'new_status' => $status, 'effective_date' => $effectiveDate, 'reason' => $reason, 'actor_id' => $actor->id]);
            $this->audit->log('employee.'.strtolower($status), $actor, $employee, ['current_status' => $previous], ['current_status' => $status]);
        });
    }
}

<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeEmployeeStatusAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, Employee $employee, string $status, string $effectiveDate, ?string $reason): void
    {
        DB::transaction(function () use ($actor, $employee, $status, $effectiveDate, $reason) {
            $previous = $employee->current_status->value;
            $this->ensureAllowedTransition($previous, $status);

            if ($employee->currentAssignment === null) {
                throw ValidationException::withMessages(['employee' => 'Karyawan tidak memiliki penempatan aktif.']);
            }

            if ($status === 'ACTIVE') {
                $employee->update(['current_status' => $status, 'exit_date' => null, 'exit_reason' => null]);
            } else {
                $employee->currentAssignment?->update(['end_date' => $effectiveDate]);
                $employee->update(['current_status' => $status, 'exit_date' => $effectiveDate, 'exit_reason' => $reason]);
            }

            $employee->statusHistories()->create(['previous_status' => $previous, 'new_status' => $status, 'effective_date' => $effectiveDate, 'reason' => $reason, 'actor_id' => $actor->id]);
            $this->audit->log('employee.'.strtolower($status), $actor, $employee, ['current_status' => $previous], ['current_status' => $status]);
        });
    }

    private function ensureAllowedTransition(string $previous, string $status): void
    {
        if (! in_array($previous, ['ACTIVE', 'PROBATION'], true)) {
            throw ValidationException::withMessages(['status' => 'Status karyawan saat ini tidak dapat diubah melalui tindakan ini.']);
        }

        if ($status === 'ACTIVE' && $previous === 'PROBATION') {
            return;
        }

        if (in_array($status, ['RESIGNED', 'TERMINATED', 'INACTIVE'], true)) {
            return;
        }

        throw ValidationException::withMessages(['status' => 'Perubahan status karyawan tidak valid.']);
    }
}

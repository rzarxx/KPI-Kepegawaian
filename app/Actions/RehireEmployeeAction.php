<?php

namespace App\Actions;

use App\Models\Division;
use App\Models\Employee;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RehireEmployeeAction
{
    public function __construct(
        private readonly OrganizationalScopeResolver $scopeResolver,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(User $actor, Employee $employee, array $data): Employee
    {
        if (! in_array($employee->current_status->value, ['RESIGNED', 'TERMINATED', 'INACTIVE'], true)) {
            throw ValidationException::withMessages(['employee' => 'Rehire hanya tersedia untuk karyawan yang sudah tidak aktif.']);
        }

        $division = Division::findOrFail($data['division_id']);
        if ($division->branch_id !== null && $division->branch_id !== (int) $data['branch_id']) {
            throw ValidationException::withMessages(['division_id' => 'Divisi tidak berada pada cabang yang dipilih.']);
        }
        if (! empty($data['sub_division_id']) && SubDivision::findOrFail($data['sub_division_id'])->division_id !== $division->id) {
            throw ValidationException::withMessages(['sub_division_id' => 'Sub divisi tidak berada pada divisi yang dipilih.']);
        }
        if (! $this->scopeResolver->allows($actor, (int) $data['branch_id'], $division->id, $data['sub_division_id'] ?? null)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $employee, $data): Employee {
            $before = $employee->toArray();
            $employee->assignments()->create([
                'branch_id' => $data['branch_id'],
                'division_id' => $data['division_id'],
                'sub_division_id' => $data['sub_division_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'start_date' => $data['effective_date'],
                'status' => 'ACTIVE',
                'reason' => $data['reason'],
                'created_by' => $actor->id,
            ]);
            $employee->statusHistories()->create([
                'previous_status' => $employee->current_status->value,
                'new_status' => 'ACTIVE',
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'actor_id' => $actor->id,
            ]);
            $employee->update(['current_status' => 'ACTIVE', 'exit_date' => null, 'exit_reason' => null]);
            $this->audit->log('employee.rehire', $actor, $employee, $before, $employee->fresh()->toArray());

            return $employee->fresh();
        });
    }
}

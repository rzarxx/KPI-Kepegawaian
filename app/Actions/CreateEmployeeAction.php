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

class CreateEmployeeAction
{
    public function __construct(private AuditLogger $auditLogger, private OrganizationalScopeResolver $scopeResolver) {}

    public function execute(User $actor, array $data): Employee
    {
        $division = Division::findOrFail($data['division_id']);
        if ($division->branch_id !== null && $division->branch_id !== (int) $data['branch_id']) {
            throw ValidationException::withMessages(['division_id' => 'Divisi tidak berada pada cabang yang dipilih.']);
        }
        if (isset($data['sub_division_id'])) {
            $subDivision = SubDivision::findOrFail($data['sub_division_id']);
            if ($subDivision->division_id !== $division->id) {
                throw ValidationException::withMessages(['sub_division_id' => 'Sub divisi tidak berada pada divisi yang dipilih.']);
            }
        }
        if (! $this->scopeResolver->allows($actor, (int) $data['branch_id'], $division->id, $data['sub_division_id'] ?? null)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $data): Employee {
            $employee = Employee::query()->create([...collect($data)->except(['branch_id', 'division_id', 'sub_division_id', 'position_id'])->all(), 'created_by' => $actor->id]);
            $assignment = $employee->assignments()->create([
                'branch_id' => $data['branch_id'], 'division_id' => $data['division_id'], 'sub_division_id' => $data['sub_division_id'] ?? null, 'position_id' => $data['position_id'] ?? null,
                'start_date' => $data['join_date'], 'status' => $data['current_status'], 'reason' => 'Penempatan awal', 'created_by' => $actor->id,
            ]);
            $employee->statusHistories()->create(['previous_status' => null, 'new_status' => $data['current_status'], 'effective_date' => $data['join_date'], 'reason' => 'Status awal', 'actor_id' => $actor->id]);
            $this->auditLogger->log('employee.create', $actor, $employee, null, $employee->only(['employee_number', 'full_name', 'current_status']), ['assignment_id' => $assignment->id]);

            return $employee;
        });
    }
}

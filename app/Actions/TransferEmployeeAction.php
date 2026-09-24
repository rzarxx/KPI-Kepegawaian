<?php

namespace App\Actions;

use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\SubDivision;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OrganizationalScopeResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferEmployeeAction
{
    public function __construct(private OrganizationalScopeResolver $scope, private AuditLogger $audit) {}

    public function execute(User $actor, Employee $employee, array $data): EmployeeAssignment
    {
        $current = $employee->currentAssignment;
        if (! $current) {
            throw ValidationException::withMessages(['employee' => 'Karyawan tidak memiliki penempatan aktif.']);
        } $division = Division::findOrFail($data['division_id']);
        if ($division->branch_id !== null && $division->branch_id !== (int) $data['branch_id']) {
            throw ValidationException::withMessages(['division_id' => 'Divisi tidak berada pada cabang yang dipilih.']);
        } if (! empty($data['sub_division_id']) && SubDivision::findOrFail($data['sub_division_id'])->division_id !== $division->id) {
            throw ValidationException::withMessages(['sub_division_id' => 'Sub divisi tidak berada pada divisi yang dipilih.']);
        } if (! $this->scope->allows($actor, $current->branch_id, $current->division_id, $current->sub_division_id) || ! $this->scope->allows($actor, (int) $data['branch_id'], $division->id, $data['sub_division_id'] ?? null)) {
            throw new AuthorizationException;
        }

return DB::transaction(function () use ($actor, $employee, $current, $data) {
            $current->update(['end_date' => $data['effective_date']]);
            $next = $employee->assignments()->create(['branch_id' => $data['branch_id'], 'division_id' => $data['division_id'], 'sub_division_id' => $data['sub_division_id'] ?? null, 'position_id' => $data['position_id'] ?? null, 'start_date' => $data['effective_date'], 'status' => 'ACTIVE', 'reason' => $data['reason'] ?? 'Mutasi', 'created_by' => $actor->id]);
            $employee->statusHistories()->create(['previous_status' => $employee->current_status->value, 'new_status' => 'MUTATED', 'effective_date' => $data['effective_date'], 'reason' => $data['reason'] ?? 'Mutasi', 'actor_id' => $actor->id]);
            $this->audit->log('employee.transfer', $actor, $employee, null, ['from_assignment' => $current->id, 'to_assignment' => $next->id]);

            return $next;
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_keeps_previous_assignment_and_audits_the_change(): void
    {
        [$actor, $employee, $branch, $division] = $this->employeeWithAccess(['employee.transfer', 'employee.view']);
        $position = Position::query()->create(['code' => 'MGR', 'name' => 'Manajer', 'level' => 'Manajerial']);

        $this->actingAs($actor)->post(route('employees.transfer', $employee), [
            'branch_id' => $branch->id, 'division_id' => $division->id, 'position_id' => $position->id,
            'effective_date' => now()->subDay()->toDateString(), 'reason' => 'Promosi internal',
        ])->assertSessionHasNoErrors();

        $this->assertSame(now()->subDay()->toDateString(), $employee->assignments()->whereNotNull('end_date')->sole()->end_date->toDateString());
        $this->assertDatabaseCount('employee_assignments', 2);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $actor->id, 'action' => 'employee.transfer']);
    }

    public function test_resigned_employee_remains_visible_in_final_assignment_scope_with_history(): void
    {
        [$actor, $employee] = $this->employeeWithAccess(['employee.change_status', 'employee.view']);

        $this->actingAs($actor)->post(route('employees.status', $employee), [
            'status' => 'RESIGNED', 'effective_date' => now()->subDay()->toDateString(), 'reason' => 'Mengundurkan diri',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'current_status' => 'RESIGNED']);
        $this->assertDatabaseHas('employee_status_histories', ['employee_id' => $employee->id, 'new_status' => 'RESIGNED']);
        $this->actingAs($actor)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('activities', 2)
                ->where('activities.0.type', 'STATUS')
                ->where('activities.1.type', 'ASSIGNMENT'));
        $this->actingAs($actor)->get(route('employees.index'))->assertSee($employee->full_name);
        $this->actingAs($actor)->get(route('employees.index', ['branch_id' => $employee->latestAssignment->branch_id]))
            ->assertOk()
            ->assertSee($employee->full_name);
    }

    public function test_create_form_only_exposes_organizational_scope_options(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('employee.create', 'web'));
        $allowed = Branch::query()->create(['code' => 'BDG', 'name' => 'Bandung']);
        Branch::query()->create(['code' => 'SBY', 'name' => 'Surabaya']);
        $actor->organizationalScopes()->create(['branch_id' => $allowed->id, 'scope_type' => 'branch', 'is_active' => true]);

        $this->actingAs($actor)->get(route('employees.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('branches', 1)
                ->where('branches.0.id', $allowed->id));
    }

    public function test_inactive_employee_can_be_rehired_without_losing_history(): void
    {
        [$actor, $employee, $branch, $division] = $this->employeeWithAccess(['employee.change_status', 'employee.view']);
        $this->actingAs($actor)->post(route('employees.status', $employee), [
            'status' => 'RESIGNED',
            'effective_date' => now()->subMonth()->toDateString(),
            'reason' => 'Mengundurkan diri',
        ])->assertSessionHasNoErrors();

        $this->actingAs($actor)->post(route('employees.rehire', $employee), [
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'effective_date' => now()->toDateString(),
            'reason' => 'Kembali bekerja',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'current_status' => 'ACTIVE']);
        $this->assertDatabaseCount('employee_assignments', 2);
        $this->assertDatabaseHas('employee_status_histories', ['employee_id' => $employee->id, 'new_status' => 'ACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $actor->id, 'action' => 'employee.rehire']);
    }

    private function employeeWithAccess(array $permissions): array
    {
        $actor = User::factory()->create();
        foreach ($permissions as $permission) {
            $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-001', 'full_name' => 'Rina Karyawan', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);

        return [$actor, $employee, $branch, $division];
    }
}

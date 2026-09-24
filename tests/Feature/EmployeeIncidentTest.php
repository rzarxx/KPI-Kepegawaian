<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeIncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoped_manager_can_create_and_resolve_incident_with_audit(): void
    {
        [$user, $employee] = $this->context(['employee.view', 'employee_incident.create', 'employee_incident.resolve']);
        IncidentCategory::query()->create(['code' => 'discipline', 'name' => 'Kedisiplinan', 'is_active' => true]);
        $this->actingAs($user)->post(route('employees.incidents.store', $employee), [
            'category' => 'discipline',
            'title' => 'Keterlambatan berulang',
            'severity' => 'HIGH',
            'description' => 'Karyawan terlambat lebih dari tiga kali dalam satu pekan.',
            'occurred_at' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
        $incident = $employee->incidents()->sole();
        $this->actingAs($user)->post(route('employees.incidents.resolve', $incident), ['resolution' => 'Pembinaan telah dilakukan'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_incidents', ['id' => $incident->id, 'status' => 'RESOLVED']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'employee.incident.resolve']);
    }

    public function test_incident_outside_scope_is_forbidden(): void
    {
        [$user] = $this->context(['employee_incident.create']);
        $branch = Branch::query()->create(['code' => 'SBY', 'name' => 'Surabaya']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'OPS', 'name' => 'Operasional']);
        $employee = Employee::query()->create(['employee_number' => 'OUT', 'full_name' => 'Di luar scope', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);
        $this->actingAs($user)->post(route('employees.incidents.store', $employee), ['category' => 'Tes', 'severity' => 'HIGH', 'description' => 'Tes', 'occurred_at' => now()->toDateString()])->assertForbidden();
    }

    public function test_direct_incident_url_requires_incident_view_permission(): void
    {
        [$user, $employee] = $this->context(['employee.view']);

        $this->actingAs($user)
            ->get(route('employees.incidents.index', $employee))
            ->assertForbidden();
    }

    private function context(array $permissions): array
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $user->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-I', 'full_name' => 'Rina', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);

        return [$user, $employee];
    }
}

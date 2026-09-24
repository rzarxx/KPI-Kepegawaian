<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\LocalRoleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_has_the_expected_permission_matrix(): void
    {
        $this->seed(AccessControlSeeder::class);

        $expected = [
            'HR Admin' => ['employee.view', 'employee.create', 'employee.update', 'employee.change_status', 'employee.transfer', 'employee.export', 'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'employee_incident.resolve', 'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'employee_document.view', 'employee_document.create', 'employee_document.delete', 'report.view', 'report.export', 'organization.view', 'organization.manage', 'user.view', 'user.create', 'user.update', 'user.disable', 'role.view', 'settings.view', 'settings.manage'],
            'HR Manager' => ['employee.view', 'employee.export', 'employee_incident.view', 'employee_incident.resolve', 'evaluation.view', 'evaluation.approve', 'evaluation.finalize', 'employee_document.view', 'report.view', 'report.export', 'organization.view', 'audit.view', 'settings.view'],
            'Branch Head' => ['employee.view', 'employee.update', 'employee.export', 'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'employee_incident.resolve', 'evaluation.view', 'evaluation.approve', 'employee_document.view', 'report.view', 'report.export', 'organization.view'],
            'Division Head' => ['employee.view', 'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'evaluation.approve', 'employee_document.view', 'report.view', 'organization.view'],
            'Sub Division Head' => ['employee.view', 'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'employee_document.view', 'organization.view'],
            'Auditor' => ['employee.view', 'employee_incident.view', 'evaluation.view', 'employee_document.view', 'report.view', 'report.export', 'audit.view', 'organization.view'],
            'Employee' => [],
        ];

        foreach ($expected as $roleName => $permissions) {
            $actual = Role::findByName($roleName)->permissions->pluck('name')->all();
            $this->assertEqualsCanonicalizing($permissions, $actual, $roleName);
        }

        $this->assertGreaterThan(count($expected['HR Admin']), Role::findByName('Super Admin')->permissions()->count());
    }

    public function test_each_role_is_denied_a_direct_url_outside_its_authority(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);

        $users = collect(['Super Admin', 'HR Admin', 'HR Manager', 'Branch Head', 'Division Head', 'Sub Division Head', 'Auditor'])
            ->mapWithKeys(fn (string $role) => [$role => User::query()->where('email', str($role)->slug('.').'@kpi.local.test')->firstOrFail()]);

        $this->actingAs($users['Super Admin'])->put(route('users.update', $users['Super Admin']), [
            'name' => $users['Super Admin']->name,
            'role' => 'Super Admin',
            'scopes' => [],
        ])->assertForbidden();
        $this->actingAs($users['HR Admin'])->get(route('audit.index'))->assertForbidden();
        $this->actingAs($users['HR Manager'])->post(route('employees.store'), [])->assertForbidden();
        $this->actingAs($users['Branch Head'])->get(route('users.index'))->assertForbidden();
        $this->actingAs($users['Division Head'])->post(route('organization.store', 'cabang'), [])->assertForbidden();
        $this->actingAs($users['Sub Division Head'])->get(route('reports.index'))->assertForbidden();
        $this->actingAs($users['Auditor'])->get(route('users.index'))->assertForbidden();

        $employeeRole = User::factory()->create();
        $employeeRole->assignRole('Employee');
        $this->actingAs($employeeRole)->get(route('dashboard'))->assertForbidden();
    }

    public function test_shared_navigation_contains_role_and_active_scope_badges(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);
        $user = User::query()->where('email', 'division.head@kpi.local.test')->firstOrFail();

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.access.role', 'Division Head')
            ->where('auth.access.scopeLabels.0', 'Cabang UAT Jakarta / SDM Jakarta')
            ->where('auth.abilities.reportView', true)
            ->where('auth.abilities.userView', false));
    }

    public function test_each_organizational_head_is_forbidden_from_opening_neighbor_employee_data(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);
        $cases = [
            'branch.head@kpi.local.test' => 'UAT-RBAC-004',
            'division.head@kpi.local.test' => 'UAT-RBAC-003',
            'sub.division.head@kpi.local.test' => 'UAT-RBAC-002',
        ];

        foreach ($cases as $email => $employeeNumber) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $employee = Employee::query()->where('employee_number', $employeeNumber)->firstOrFail();

            $this->actingAs($user)->get(route('employees.show', $employee))->assertForbidden();
            $this->actingAs($user)->get(route('employees.incidents.index', $employee))->assertForbidden();
        }
    }

    public function test_invalid_global_scope_cannot_expand_a_division_head_access(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);
        $user = User::query()->where('email', 'division.head@kpi.local.test')->firstOrFail();
        $user->organizationalScopes()->create([
            'scope_type' => 'organization',
            'is_active' => true,
        ]);
        $outside = Employee::query()->where('employee_number', 'UAT-RBAC-003')->firstOrFail();

        $this->actingAs($user)->get(route('employees.show', $outside))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.access.scopeLabels', ['Cabang UAT Jakarta / SDM Jakarta']));
    }

    public function test_scoped_auditor_cannot_open_organization_wide_audit_log(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);
        $user = User::query()->where('email', 'auditor@kpi.local.test')->firstOrFail();
        $branchId = Branch::query()->where('code', 'UAT-JKT')->value('id');
        $user->organizationalScopes()->delete();
        $user->organizationalScopes()->create([
            'branch_id' => $branchId,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.abilities.auditView', false)
            ->where('auth.access.scopeLabels', ['Cabang UAT Jakarta']));
    }
}

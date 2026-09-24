<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeQueryService;
use Database\Seeders\LocalRoleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocalRoleAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_active_accounts_only_for_roles_with_an_available_portal(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);

        $roles = Role::query()->where('guard_name', 'web')->get();

        foreach ($roles->where('name', '!=', 'Employee') as $role) {
            $email = str($role->name)->slug('.').'@kpi.local.test';
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->is_active);
            $this->assertTrue($user->hasExactRoles($role));
            $this->assertNotNull($user->email_verified_at);

            if ($role->name === 'Super Admin') {
                $this->assertSame(0, $user->organizationalScopes()->count());
            } else {
                $this->assertSame(1, $user->organizationalScopes()->where('is_active', true)->count());
            }
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'employee@kpi.local.test',
            'is_active' => true,
        ]);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);
        $this->seed(LocalRoleAccountSeeder::class);

        $roleCount = Role::query()->where('guard_name', 'web')->count();

        $this->assertSame($roleCount - 1, User::query()->where('email', 'like', '%@kpi.local.test')->where('is_active', true)->count());
        $this->assertSame(4, Employee::query()->where('employee_number', 'like', 'UAT-RBAC-%')->count());
    }

    public function test_uat_organization_scopes_produce_different_visible_employee_sets(): void
    {
        $this->seed(LocalRoleAccountSeeder::class);
        $employees = app(EmployeeQueryService::class);

        $expected = [
            'Super Admin' => 4,
            'HR Admin' => 4,
            'HR Manager' => 4,
            'Branch Head' => 3,
            'Division Head' => 2,
            'Sub Division Head' => 1,
            'Auditor' => 4,
        ];

        foreach ($expected as $role => $count) {
            $user = User::query()->where('email', str($role)->slug('.').'@kpi.local.test')->firstOrFail();
            $this->assertSame($count, $employees->visibleTo($user)->where('employee_number', 'like', 'UAT-RBAC-%')->count(), $role);
        }
    }
}

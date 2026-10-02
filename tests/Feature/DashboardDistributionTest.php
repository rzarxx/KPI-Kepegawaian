<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_distribution_data(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('evaluation.view', 'web'),
        );
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-D-01', 'full_name' => 'Budi', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);

        $this->actingAs($actor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('distributions.byDivision', 1)
                ->has('distributions.byBranch', 1)
                ->has('distributions.byStatus', 1)
            );
    }

    public function test_division_head_cannot_transfer_employee_without_manage_track_record(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('employee.transfer', 'web'),
        );
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'TI']);
        $newDivision = Division::query()->create(['branch_id' => $branch->id, 'code' => 'HR', 'name' => 'SDM']);
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-TR-01', 'full_name' => 'Andi', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);

        $this->actingAs($actor)
            ->post(route('employees.transfer', $employee), [
                'branch_id' => $branch->id,
                'division_id' => $newDivision->id,
                'effective_date' => now()->toDateString(),
                'reason' => 'Mutasi organisasi',
            ])
            ->assertForbidden();
    }

    public function test_hr_admin_can_transfer_employee_with_manage_track_record(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(
            Permission::findOrCreate('employee.view', 'web'),
            Permission::findOrCreate('employee.transfer', 'web'),
            Permission::findOrCreate('employee.manage_track_record', 'web'),
        );
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'TI']);
        $newDivision = Division::query()->create(['branch_id' => $branch->id, 'code' => 'HR', 'name' => 'SDM']);
        $actor->organizationalScopes()->create(['branch_id' => $branch->id, 'scope_type' => 'branch', 'is_active' => true]);
        $employee = Employee::query()->create(['employee_number' => 'EMP-TR-02', 'full_name' => 'Budi', 'join_date' => now()->subYear(), 'current_status' => 'ACTIVE']);
        $employee->assignments()->create(['branch_id' => $branch->id, 'division_id' => $division->id, 'start_date' => now()->subYear(), 'status' => 'ACTIVE']);

        $this->actingAs($actor)
            ->post(route('employees.transfer', $employee), [
                'branch_id' => $branch->id,
                'division_id' => $newDivision->id,
                'effective_date' => now()->toDateString(),
                'reason' => 'Mutasi ke divisi baru',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_branding_settings_page_requires_settings_manage_permission(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('employee.view', 'web'));

        $this->actingAs($actor)
            ->get(route('settings.appearance'))
            ->assertForbidden();
    }

    public function test_super_admin_can_update_branding_settings(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('settings.manage', 'web'));

        $this->actingAs($actor)
            ->put(route('settings.appearance.update'), [
                'app_name' => 'KPI Perusahaan Baru',
                'primary_color' => '#2563EB',
                'footer_text' => '© 2026 Perusahaan',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('KPI Perusahaan Baru', AppSetting::getValue('app_name'));
        $this->assertSame('#2563EB', AppSetting::getValue('primary_color'));
        $this->assertSame('© 2026 Perusahaan', AppSetting::getValue('footer_text'));
    }

    public function test_branding_is_blocked_during_impersonation(): void
    {
        $this->seed(AccessControlSeeder::class);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $target = User::factory()->create();
        $target->assignRole('HR Admin');

        $this->actingAs($superAdmin)
            ->post(route('impersonation.start', $target), ['reason' => 'Memeriksa pengaturan tampilan untuk investigasi']);

        $this->get(route('settings.appearance'))
            ->assertForbidden();
    }
}

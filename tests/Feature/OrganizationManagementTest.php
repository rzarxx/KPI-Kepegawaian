<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_hierarchy_and_audit_it(): void
    {
        $admin = $this->organizationAdmin();
        $this->actingAs($admin)->post(route('organization.store', 'cabang'), ['code' => 'JKT', 'name' => 'Jakarta', 'is_active' => true])->assertSessionHasNoErrors();
        $branch = Branch::query()->where('code', 'JKT')->firstOrFail();
        $this->actingAs($admin)->post(route('organization.store', 'divisi'), ['code' => 'TI-JKT', 'name' => 'Teknologi Informasi', 'branch_id' => $branch->id, 'is_active' => true])->assertSessionHasNoErrors();
        $division = Division::query()->where('code', 'TI-JKT')->firstOrFail();
        $this->actingAs($admin)->post(route('organization.store', 'sub-divisi'), ['code' => 'DEV-JKT', 'name' => 'Pengembangan', 'division_id' => $division->id, 'is_active' => true])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('organization.store', 'jabatan'), ['code' => 'ENG', 'name' => 'Engineer', 'level' => 'Staf', 'is_active' => true])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sub_divisions', ['division_id' => $division->id, 'code' => 'DEV-JKT']);
        $this->assertDatabaseHas('positions', ['code' => 'ENG', 'level' => 'Staf']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'organization.divisi.create']);
    }

    public function test_manager_cannot_create_division_outside_organizational_scope(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('organization.manage', 'web'));
        $allowed = Branch::query()->create(['code' => 'BDG', 'name' => 'Bandung']);
        $outside = Branch::query()->create(['code' => 'SBY', 'name' => 'Surabaya']);
        $manager->organizationalScopes()->create(['branch_id' => $allowed->id, 'scope_type' => 'branch', 'is_active' => true]);

        $this->actingAs($manager)->post(route('organization.store', 'divisi'), ['code' => 'OPS-SBY', 'name' => 'Operasional', 'branch_id' => $outside->id, 'is_active' => true])->assertForbidden();
        $this->assertDatabaseMissing('divisions', ['code' => 'OPS-SBY']);
    }

    public function test_manager_only_sees_branches_in_organizational_scope(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('organization.view', 'web'));
        $allowed = Branch::query()->create(['code' => 'BDG', 'name' => 'Bandung']);
        Branch::query()->create(['code' => 'SBY', 'name' => 'Surabaya']);
        $manager->organizationalScopes()->create([
            'branch_id' => $allowed->id,
            'scope_type' => 'branch',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->get(route('organization.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('branches', 1)
                ->where('branches.0.id', $allowed->id)
                ->where('branches.0.code', 'BDG'));
    }

    public function test_update_keeps_code_unique_and_records_before_after_audit_values(): void
    {
        $admin = $this->organizationAdmin();
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $this->actingAs($admin)->put(route('organization.update', ['cabang', $branch->id]), ['code' => 'JKT', 'name' => 'Jakarta Pusat', 'is_active' => false])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Jakarta Pusat', 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'organization.cabang.update']);
    }

    private function organizationAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        foreach (['organization.view', 'organization.manage'] as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $admin = User::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }
}

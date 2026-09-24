<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Division;
use App\Models\ImpersonationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_provision_user_with_organizational_scope_and_audit_log(): void
    {
        $superAdmin = $this->superAdmin();
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $division = Division::query()->create(['branch_id' => $branch->id, 'code' => 'TI', 'name' => 'Teknologi Informasi']);
        Role::findOrCreate('Division Head', 'web');

        $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Kepala Divisi Jakarta',
            'email' => 'kepala@example.test',
            'password' => 'kata-sandi-yang-kuat',
            'role' => 'Division Head',
            'branch_id' => $branch->id,
            'division_id' => $division->id,
        ])->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'kepala@example.test')->firstOrFail();
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('Division Head'));
        $this->assertDatabaseHas('organizational_scopes', ['user_id' => $user->id, 'branch_id' => $branch->id, 'division_id' => $division->id, 'scope_type' => 'division']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $superAdmin->id, 'auditable_id' => $user->id, 'action' => 'user.create']);
    }

    public function test_division_head_cannot_be_provisioned_with_branch_wide_scope(): void
    {
        $superAdmin = $this->superAdmin();
        $branch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        Role::findOrCreate('Division Head', 'web');

        $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Scope Terlalu Luas',
            'email' => 'scope-luas@example.test',
            'password' => 'kata-sandi-yang-kuat',
            'role' => 'Division Head',
            'branch_id' => $branch->id,
        ])->assertSessionHasErrors([
            'scopes' => 'Branch Head wajib dikunci ke satu cabang; Division Head ke satu divisi; dan Sub Division Head ke satu sub divisi.',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'scope-luas@example.test']);
    }

    public function test_non_super_admin_cannot_disable_super_admin_account(): void
    {
        $target = $this->superAdmin();
        $manager = User::factory()->create();
        Permission::findOrCreate('user.disable', 'web');
        $manager->givePermissionTo('user.disable');

        $this->actingAs($manager)->post(route('users.toggle', $target))->assertForbidden();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_super_admin_can_impersonate_active_non_admin_and_return_safely(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Pengguna Target']);
        Permission::findOrCreate('impersonation.start', 'web');
        $superAdmin->givePermissionTo('impersonation.start');

        $this->actingAs($superAdmin)->post(route('impersonation.start', $target), ['reason' => 'Memeriksa pengalaman pengguna untuk investigasi akses.'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($target);
        $this->assertDatabaseHas('impersonation_sessions', ['impersonator_id' => $superAdmin->id, 'target_id' => $target->id, 'ended_at' => null]);
        $this->post(route('users.store'), [])->assertForbidden();

        $this->post(route('impersonation.end'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($superAdmin);
        $this->assertDatabaseHas('impersonation_sessions', ['impersonator_id' => $superAdmin->id, 'target_id' => $target->id, 'ended_reason' => 'ended']);
    }

    public function test_expired_impersonation_is_ended_before_the_target_can_continue(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create();
        $superAdmin->givePermissionTo(Permission::findOrCreate('impersonation.start', 'web'));
        $this->actingAs($superAdmin)->post(route('impersonation.start', $target), ['reason' => 'Memeriksa pengalaman pengguna untuk investigasi akses.']);
        $session = ImpersonationSession::query()->firstOrFail();
        $session->update(['expires_at' => now()->subMinute()]);

        $this->get(route('employees.index'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($superAdmin);
        $this->assertDatabaseHas('impersonation_sessions', ['id' => $session->id, 'ended_reason' => 'timeout']);
    }

    public function test_impersonated_user_cannot_open_or_change_the_target_profile(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Nama Awal']);
        $superAdmin->givePermissionTo(Permission::findOrCreate('impersonation.start', 'web'));
        $this->actingAs($superAdmin)->post(route('impersonation.start', $target), [
            'reason' => 'Memeriksa pengalaman pengguna untuk investigasi akses.',
        ]);

        $this->get(route('profile.edit'))->assertForbidden();
        $this->patch(route('profile.update'), [
            'name' => 'Nama Diubah',
            'email' => $target->email,
        ])->assertForbidden();

        $this->assertSame('Nama Awal', $target->fresh()->name);
    }

    public function test_super_admin_can_preserve_multiple_scopes_when_updating_user(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create();
        $role = Role::findOrCreate('HR Manager', 'web');
        $target->assignRole($role);
        $firstBranch = Branch::query()->create(['code' => 'JKT', 'name' => 'Jakarta']);
        $secondBranch = Branch::query()->create(['code' => 'BDG', 'name' => 'Bandung']);
        $target->organizationalScopes()->create(['branch_id' => $firstBranch->id, 'scope_type' => 'branch', 'is_active' => true]);

        $this->actingAs($superAdmin)->put(route('users.update', $target), [
            'name' => $target->name,
            'role' => 'HR Manager',
            'scopes' => [
                ['branch_id' => $firstBranch->id, 'division_id' => null, 'sub_division_id' => null],
                ['branch_id' => $secondBranch->id, 'division_id' => null, 'sub_division_id' => null],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $target->organizationalScopes()->count());
        $this->assertDatabaseHas('organizational_scopes', ['user_id' => $target->id, 'branch_id' => $firstBranch->id]);
        $this->assertDatabaseHas('organizational_scopes', ['user_id' => $target->id, 'branch_id' => $secondBranch->id]);
    }

    public function test_impersonated_manager_sees_read_only_evaluation_configuration(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create();
        $superAdmin->givePermissionTo(Permission::findOrCreate('impersonation.start', 'web'));
        $target->givePermissionTo(
            Permission::findOrCreate('evaluation.view', 'web'),
            Permission::findOrCreate('organization.manage', 'web'),
            Permission::findOrCreate('settings.manage', 'web'),
        );
        $this->actingAs($superAdmin)->post(route('impersonation.start', $target), [
            'reason' => 'Memeriksa pengalaman pengguna untuk investigasi akses.',
        ]);

        $this->get(route('evaluations.configuration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Evaluations/Configuration')
                ->where('canManage', false)
                ->where('canManageSettings', false));
        $this->post(route('evaluation-periods.store'), [])->assertForbidden();
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        foreach (['user.view', 'user.create', 'user.update', 'user.disable', 'role.manage'] as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

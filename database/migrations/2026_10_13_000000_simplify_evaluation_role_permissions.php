<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $retiredPermissions = [
        'HR Admin' => ['evaluation.create', 'evaluation.update', 'evaluation.submit', 'calibration.manage'],
        'HR Manager' => ['evaluation.approve', 'evaluation.finalize', 'calibration.manage'],
        'Branch Head' => ['evaluation.approve'],
        'Division Head' => ['evaluation.create', 'evaluation.update', 'evaluation.submit', 'evaluation.approve'],
        'Sub Division Head' => ['evaluation.submit'],
        'Employee' => ['evaluation.self_assess'],
    ];

    public function up(): void
    {
        $reopen = Permission::findOrCreate('evaluation.reopen', 'web');

        foreach (['Super Admin', 'HR Admin', 'HR Manager'] as $roleName) {
            Role::query()->where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($reopen);
        }

        foreach ($this->retiredPermissions as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            foreach ($permissions as $permissionName) {
                $permission = Permission::query()->where('name', $permissionName)->where('guard_name', 'web')->first();
                if ($permission && $role->hasPermissionTo($permission)) {
                    $role->revokePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ($this->retiredPermissions as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            foreach ($permissions as $permissionName) {
                $permission = Permission::query()->where('name', $permissionName)->where('guard_name', 'web')->first();
                if ($permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $reopen = Permission::query()->where('name', 'evaluation.reopen')->where('guard_name', 'web')->first();
        if ($reopen) {
            foreach (['Super Admin', 'HR Admin', 'HR Manager'] as $roleName) {
                $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && $role->hasPermissionTo($reopen)) {
                    $role->revokePermissionTo($reopen);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

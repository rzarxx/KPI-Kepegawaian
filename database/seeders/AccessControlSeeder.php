<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'employee.view', 'employee.create', 'employee.update', 'employee.change_status', 'employee.transfer', 'employee.export',
            'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'employee_incident.resolve',
            'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'evaluation.approve', 'evaluation.finalize',
            'report.view', 'report.export',
            'employee_document.view', 'employee_document.create', 'employee_document.delete',
            'organization.view', 'organization.manage',
            'user.view', 'user.create', 'user.update', 'user.disable',
            'role.view', 'role.manage',
            'audit.view',
            'settings.view', 'settings.manage',
            'impersonation.start',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()->pluck('name')->all();

        $roles = [
            'Super Admin' => $allPermissions,
            'HR Admin' => [
                'employee.view', 'employee.create', 'employee.update', 'employee.change_status', 'employee.transfer', 'employee.export',
                'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'employee_incident.resolve',
                'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit',
                'employee_document.view', 'employee_document.create', 'employee_document.delete',
                'report.view', 'report.export', 'organization.view', 'organization.manage',
                'user.view', 'user.create', 'user.update', 'user.disable', 'role.view',
                'settings.view', 'settings.manage',
            ],
            'HR Manager' => [
                'employee.view', 'employee.export',
                'employee_incident.view', 'employee_incident.resolve',
                'evaluation.view', 'evaluation.approve', 'evaluation.finalize',
                'employee_document.view',
                'report.view', 'report.export', 'organization.view', 'audit.view', 'settings.view',
            ],
            'Branch Head' => [
                'employee.view', 'employee.update', 'employee.export',
                'employee_incident.view', 'employee_incident.create', 'employee_incident.update', 'employee_incident.resolve',
                'evaluation.view', 'evaluation.approve',
                'employee_document.view', 'report.view', 'report.export', 'organization.view',
            ],
            'Division Head' => [
                'employee.view',
                'employee_incident.view', 'employee_incident.create', 'employee_incident.update',
                'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit', 'evaluation.approve',
                'employee_document.view', 'report.view', 'organization.view',
            ],
            'Sub Division Head' => [
                'employee.view',
                'employee_incident.view', 'employee_incident.create', 'employee_incident.update',
                'evaluation.view', 'evaluation.create', 'evaluation.update', 'evaluation.submit',
                'employee_document.view', 'organization.view',
            ],
            'Auditor' => ['employee.view', 'employee_incident.view', 'evaluation.view', 'employee_document.view', 'report.view', 'report.export', 'audit.view', 'organization.view'],
            'Employee' => [],
        ];

        foreach ($roles as $name => $rolePermissions) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

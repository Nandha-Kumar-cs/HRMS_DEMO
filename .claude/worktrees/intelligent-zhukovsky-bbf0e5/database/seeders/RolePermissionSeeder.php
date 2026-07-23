<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ──────────────────────────────────────────
        $modules = [
            'employees'  => ['view', 'create', 'edit', 'delete', 'import'],
            'payroll'    => ['view', 'create', 'edit', 'delete', 'generate'],
            'attendance' => ['view', 'create', 'edit', 'import'],
            'leaves'     => ['view', 'create', 'approve', 'reject'],
            'loans'      => ['view', 'create', 'edit', 'delete', 'approve'],
            'increments' => ['view', 'create', 'edit', 'delete'],
            'assets'     => ['view', 'create', 'edit', 'delete', 'assign'],
            'documents'  => ['view', 'upload', 'delete'],
            'benefits'   => ['view', 'create', 'edit', 'delete'],
            'bonuses'    => ['view', 'create', 'edit', 'approve', 'reject'],
            'reports'    => ['view'],
            'training'   => ['view', 'create', 'edit', 'delete'],
            'settings'   => ['view', 'edit'],
            'users'      => ['view', 'create', 'edit', 'delete'],
            'roles'      => ['view', 'create', 'edit', 'delete'],
        ];

        foreach ($modules as $module => $features) {
            foreach ($features as $feature) {
                Permission::firstOrCreate(
                    ['slug' => "{$module}.{$feature}"],
                    [
                        'name'    => ucfirst($module) . ' — ' . ucfirst($feature),
                        'module'  => $module,
                        'feature' => $feature,
                    ]
                );
            }
        }

        // ── Roles ────────────────────────────────────────────────
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Full system access', 'is_system' => true]
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Manage team and payroll', 'is_system' => true]
        );

        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'View own records', 'is_system' => true]
        );

        // Admin gets all permissions
        $adminRole->permissions()->sync(Permission::pluck('id'));

        // Manager: everything except user/role management
        $managerPerms = Permission::whereNotIn('module', ['users', 'roles'])
            ->whereNotIn('feature', ['delete'])
            ->pluck('id');
        $managerRole->permissions()->sync($managerPerms);

        // Staff: view only on most modules
        $staffPerms = Permission::where('feature', 'view')
            ->whereIn('module', ['payroll', 'attendance', 'leaves', 'training', 'reports'])
            ->pluck('id');
        $staffRole->permissions()->sync($staffPerms);
    }
}

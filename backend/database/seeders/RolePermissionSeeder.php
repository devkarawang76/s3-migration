<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'tickets.view',
            'tickets.create',
            'tickets.edit',
            'tickets.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Roles
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $operator = Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'web',
        ]);

        $superAdmin->givePermissionTo(Permission::all());
        $admin->givePermissionTo([
            'users.view',
            'tickets.view',
            'tickets.create',
            'tickets.edit',
        ]);
        $operator->givePermissionTo([
            'tickets.view',
            'tickets.create',
            'tickets.edit',
        ]);
    }
}
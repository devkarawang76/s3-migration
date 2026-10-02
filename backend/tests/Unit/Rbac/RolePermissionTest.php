<?php

namespace Tests\Unit\Rbac;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_seeder_creates_roles_and_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Role::where('name', 'super-admin')->exists());
        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertTrue(Role::where('name', 'operator')->exists());

        $this->assertTrue(Permission::where('name', 'users.view')->exists());
        $this->assertTrue(Permission::where('name', 'users.create')->exists());
        $this->assertTrue(Permission::where('name', 'users.edit')->exists());
        $this->assertTrue(Permission::where('name', 'users.delete')->exists());
        $this->assertTrue(Permission::where('name', 'tickets.view')->exists());
        $this->assertTrue(Permission::where('name', 'tickets.create')->exists());
        $this->assertTrue(Permission::where('name', 'tickets.edit')->exists());
        $this->assertTrue(Permission::where('name', 'tickets.delete')->exists());
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = Role::where('name', 'super-admin')->first();
        $this->assertNotNull($superAdmin);
        $this->assertGreaterThan(0, $superAdmin->permissions()->count());
    }
}
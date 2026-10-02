<?php

namespace Tests\Feature\Seeder;

use Database\Seeders\AdminSeeder;
use Database\Seeders\EnumMappingSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_runs(): void
    {
        $this->seed(RoleSeeder::class);
        $this->assertTrue(true);
    }

    public function test_permission_seeder_runs(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->assertTrue(true);
    }

    public function test_admin_seeder_runs(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(AdminSeeder::class);
        $this->assertTrue(true);
    }

    public function test_enum_mapping_seeder_runs(): void
    {
        $this->seed(EnumMappingSeeder::class);
        $this->assertTrue(true);
    }
}
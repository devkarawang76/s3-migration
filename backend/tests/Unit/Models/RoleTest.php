<?php

namespace Tests\Unit\Models;

use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_role_model_exists(): void
    {
        $role = new Role();
        $this->assertInstanceOf(Role::class, $role);
    }
}
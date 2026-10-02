<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Admin;
use App\Models\LegacyUser;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_model_extends_legacy_model(): void
    {
        $user = new User();
        $this->assertInstanceOf(\App\Models\LegacyModel::class, $user);
    }

    public function test_admin_model_extends_authenticatable(): void
    {
        $admin = new Admin();
        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $admin);
    }

    public function test_legacy_user_uses_legacy_table(): void
    {
        $user = new LegacyUser();
        $this->assertEquals('users', $user->getTable());
    }

    public function test_user_uses_normalized_table(): void
    {
        $user = new User();
        $this->assertEquals('users', $user->getTable());
    }
}

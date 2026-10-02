<?php

namespace Tests\Unit\Auth;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GuardConfigTest extends TestCase
{
    public function test_web_guard_uses_legacy_provider(): void
    {
        $guards = Config::get('auth.guards');
        $this->assertArrayHasKey('web', $guards);
        $this->assertEquals('legacy', $guards['web']['provider']);
    }

    public function test_api_guard_uses_legacy_provider(): void
    {
        $guards = Config::get('auth.guards');
        $this->assertArrayHasKey('api', $guards);
        $this->assertEquals('legacy', $guards['api']['provider']);
    }

    public function test_admin_guard_uses_admins_provider(): void
    {
        $guards = Config::get('auth.guards');
        $this->assertArrayHasKey('admin', $guards);
        $this->assertEquals('admins', $guards['admin']['provider']);
    }

    public function test_providers_are_configured(): void
    {
        $providers = Config::get('auth.providers');
        $this->assertArrayHasKey('legacy', $providers);
        $this->assertArrayHasKey('admins', $providers);
    }
}
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_returns_spa_shell_with_mount_point(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<div id="app"></div>', false)
            ->assertSee('csrf-token', false)
            ->assertSee('type="module"', false);
    }

    public function test_chat_route_returns_spa_shell(): void
    {
        $this->get('/chat')
            ->assertOk()
            ->assertSee('<div id="app"></div>', false)
            ->assertSee('type="module"', false);
    }

    public function test_archive_route_returns_spa_shell(): void
    {
        $this->get('/archive')
            ->assertOk()
            ->assertSee('<div id="app"></div>', false)
            ->assertSee('type="module"', false);
    }

    public function test_staff_users_route_returns_spa_shell(): void
    {
        $this->get('/chat/staff-users')
            ->assertOk()
            ->assertSee('<div id="app"></div>', false)
            ->assertSee('type="module"', false);
    }
}

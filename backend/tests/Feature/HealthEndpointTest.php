<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_returns_ok_when_database_available(): void
    {
        $response = $this->getJson('/health/ready');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok');
    }

    public function test_up_returns_success(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }
}

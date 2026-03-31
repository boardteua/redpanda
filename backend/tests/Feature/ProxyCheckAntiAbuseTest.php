<?php

namespace Tests\Feature;

use App\Models\BannedIp;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProxyCheckAntiAbuseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    public function test_register_denied_for_proxy_ip_and_persists_event(): void
    {
        config()->set('services.proxycheck.enabled', true);
        config()->set('services.proxycheck.key', 'test-key');

        $ip = '1.2.3.4';
        Http::fake([
            "https://proxycheck.io/v2/{$ip}*" => Http::response([
                'status' => 'ok',
                $ip => [
                    'proxy' => 'yes',
                    'type' => 'VPN',
                    'risk' => '90',
                ],
            ], 200),
        ]);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'TestUser',
                'email' => 'test@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'proxycheck_denied');

        $this->assertDatabaseHas('ban_evasion_events', [
            'ip' => $ip,
            'action' => 'proxycheck_denied',
        ]);
    }

    public function test_chat_post_denied_for_proxy_ip(): void
    {
        config()->set('services.proxycheck.enabled', true);
        config()->set('services.proxycheck.key', 'test-key');

        $ip = '5.6.7.8';
        Http::fake([
            "https://proxycheck.io/v2/{$ip}*" => Http::response([
                'status' => 'ok',
                $ip => [
                    'proxy' => 'yes',
                    'type' => 'VPN',
                    'risk' => '80',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->actingAs($user, 'web')
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => 'blocked',
                'client_message_id' => 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380a99',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'proxycheck_denied');
    }

    public function test_proxycheck_outage_does_not_block_register(): void
    {
        config()->set('services.proxycheck.enabled', true);
        config()->set('services.proxycheck.key', 'test-key');

        $ip = '9.9.9.9';
        Http::fake([
            "https://proxycheck.io/v2/{$ip}*" => Http::response([], 500),
        ]);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'OkUser',
                'email' => 'ok@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ])
            ->assertCreated();
    }

    public function test_proxycheck_logical_failure_status_does_not_block_register(): void
    {
        config()->set('services.proxycheck.enabled', true);
        config()->set('services.proxycheck.key', 'test-key');

        $ip = '9.9.9.10';
        Http::fake([
            "https://proxycheck.io/v2/{$ip}*" => Http::response([
                'status' => 'denied',
                'message' => 'quota exceeded',
            ], 200),
        ]);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'OkUser2',
                'email' => 'ok2@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ])
            ->assertCreated();
    }

    public function test_banned_ip_request_persists_ban_evasion_event(): void
    {
        config()->set('services.proxycheck.enabled', false);

        $ip = '10.0.0.123';
        BannedIp::query()->create(['ip' => $ip]);

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson('/api/v1/landing')
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ з цієї IP-адреси заблоковано.');

        $this->assertDatabaseHas('ban_evasion_events', [
            'ip' => $ip,
            'action' => 'banned_ip_request',
        ]);
    }
}


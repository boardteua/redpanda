<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config([
            'services.web_push.vapid.subject' => 'mailto:test@example.com',
            'services.web_push.vapid.public_key' => 'BEl62iUYgUiv8wJH4e2x9x4f0tMznEtCG2l9VmOAXoJ1Bs8LJIYurx8WcN6iYG3PaY5E9OQj1YxDCEV4VpWw2Xc',
            'services.web_push.vapid.private_key' => '8L3zVwbD3Y8h9NP7VDaRao7IhiHBpjz2uVH54camz1I',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleSubscription(): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake-endpoint-123',
            'keys' => [
                'p256dh' => 'BEl62iUYgUiv8wJH4e2x9x4f0tMznEtCG2l9VmOAXoJ1Bs8LJIYurx8WcN6iYG3PaY5E9OQj1YxDCEV4VpWw2Xc',
                'auth' => 'abc123def456ghi789jkl012',
            ],
            'contentEncoding' => 'aes128gcm',
        ];
    }

    public function test_registered_user_can_store_push_subscription(): void
    {
        $user = User::factory()->create();

        $response = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/push/subscriptions', [
                'subscription' => $this->sampleSubscription(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.endpoint', $this->sampleSubscription()['endpoint'])
            ->assertJsonPath('data.content_encoding', 'aes128gcm');

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint_hash' => PushSubscription::hashEndpoint($this->sampleSubscription()['endpoint']),
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_guest_cannot_store_push_subscription(): void
    {
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/push/subscriptions', [
                'subscription' => $this->sampleSubscription(),
            ])
            ->assertForbidden();
    }

    public function test_registered_user_can_delete_own_push_subscription_idempotently(): void
    {
        $user = User::factory()->create();
        PushSubscription::query()->create([
            'user_id' => $user->id,
            'endpoint' => $this->sampleSubscription()['endpoint'],
            'endpoint_hash' => PushSubscription::hashEndpoint($this->sampleSubscription()['endpoint']),
            'public_key' => $this->sampleSubscription()['keys']['p256dh'],
            'auth_token' => $this->sampleSubscription()['keys']['auth'],
            'content_encoding' => 'aes128gcm',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/push/subscriptions', [
                'endpoint' => $this->sampleSubscription()['endpoint'],
            ])
            ->assertOk()
            ->assertJsonPath('meta.ok', true)
            ->assertJsonPath('meta.removed', 1);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/push/subscriptions', [
                'endpoint' => $this->sampleSubscription()['endpoint'],
            ])
            ->assertOk()
            ->assertJsonPath('meta.removed', 0);
    }
}

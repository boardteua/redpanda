<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Скрізний happy-path по HTTP API, який використовує SPA після входу (T14).
 */
class IntegrationFlowApiTest extends TestCase
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

    public function test_registered_user_chat_archive_social_api_surfaces(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        Room::query()->create([
            'room_name' => 'Members',
            'topic' => null,
            'access' => 1,
        ]);

        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/messages")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $clientId = (string) Str::uuid();
        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$public->room_id}/messages", [
                'message' => 'integration hello',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages')
            ->assertOk();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/private/conversations')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/friends')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/ignores')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_guest_public_room_only_and_can_post(): void
    {
        Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        Room::query()->create([
            'room_name' => 'Registered',
            'topic' => null,
            'access' => 1,
        ]);

        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $publicId = (int) Room::query()->where('access', 0)->value('room_id');

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$publicId}/messages", [
                'message' => 'guest integration',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated();
    }
}

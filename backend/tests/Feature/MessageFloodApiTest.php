<?php

namespace Tests\Feature;

use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Chat\MessageFloodGate;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessageFloodApiTest extends TestCase
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

    public function test_third_room_message_returns_429_when_flood_enabled(): void
    {
        Cache::flush();

        ChatSetting::query()->update([
            'message_flood_enabled' => true,
            'message_flood_max_messages' => 2,
            'message_flood_window_seconds' => 3600,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();

        $post = function (string $uuid) use ($room, $user) {
            return $this->from(config('app.url'))
                ->actingAs($user, 'web')
                ->withHeaders($this->statefulHeaders())
                ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                    'message' => 'x',
                    'client_message_id' => $uuid,
                ]);
        };

        $post((string) Str::uuid())->assertCreated();
        $post((string) Str::uuid())->assertCreated();
        $post((string) Str::uuid())
            ->assertStatus(429)
            ->assertJsonPath('code', MessageFloodGate::ERROR_CODE);
    }

    public function test_duplicate_client_message_id_does_not_consume_extra_flood_slots(): void
    {
        Cache::flush();

        ChatSetting::query()->update([
            'message_flood_enabled' => true,
            'message_flood_max_messages' => 2,
            'message_flood_window_seconds' => 3600,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();
        $cid = (string) Str::uuid();

        $post = function () use ($room, $user, $cid) {
            return $this->from(config('app.url'))
                ->actingAs($user, 'web')
                ->withHeaders($this->statefulHeaders())
                ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                    'message' => 'x',
                    'client_message_id' => $cid,
                ]);
        };

        $post()->assertCreated();
        $post()->assertOk()->assertJsonPath('meta.duplicate', true);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'y',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'z',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertStatus(429);
    }

    public function test_vip_is_exempt_from_message_flood(): void
    {
        Cache::flush();

        ChatSetting::query()->update([
            'message_flood_enabled' => true,
            'message_flood_max_messages' => 1,
            'message_flood_window_seconds' => 3600,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $vip = User::factory()->vip()->create();

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'a',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated();

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'b',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated();
    }
}

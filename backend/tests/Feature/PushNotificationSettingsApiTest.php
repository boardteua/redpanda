<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\UserWebPushPrivatePeerMute;
use App\Models\UserWebPushRoomMute;
use App\Services\Push\WebPushPreferenceEvaluator;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushNotificationSettingsApiTest extends TestCase
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

    public function test_guest_cannot_read_push_notification_settings(): void
    {
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/push/notification-settings')
            ->assertForbidden();
    }

    public function test_registered_user_gets_default_push_settings(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/push/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.web_push_enabled', true)
            ->assertJsonPath('data.muted_rooms', [])
            ->assertJsonPath('data.muted_private_peers', []);
    }

    public function test_global_off_persists_and_clears_delivery_path_via_evaluator(): void
    {
        $user = User::factory()->create(['web_push_master_enabled' => true]);
        $room = Room::query()->create([
            'room_name' => 'Main',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/push/notification-settings', [
                'web_push_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.web_push_enabled', false);

        $user->refresh();
        $this->assertFalse($user->web_push_master_enabled);

        $eval = new WebPushPreferenceEvaluator;
        $this->assertFalse($eval->shouldDeliverRoomWebPush($user, $room));
    }

    public function test_patch_replaces_room_and_peer_mutes(): void
    {
        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'A',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $peer = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/push/notification-settings', [
                'muted_room_ids' => [(int) $room->room_id],
                'muted_private_peer_ids' => [(int) $peer->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.muted_rooms.0.room_id', (int) $room->room_id)
            ->assertJsonPath('data.muted_private_peers.0.user_id', (int) $peer->id);

        $this->assertDatabaseHas('user_web_push_room_mutes', [
            'user_id' => $user->id,
            'room_id' => $room->room_id,
        ]);
        $this->assertDatabaseHas('user_web_push_private_peer_mutes', [
            'user_id' => $user->id,
            'peer_user_id' => $peer->id,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/push/notification-settings', [
                'muted_room_ids' => [],
                'muted_private_peer_ids' => [],
            ])
            ->assertOk();

        $this->assertSame(0, UserWebPushRoomMute::query()->where('user_id', $user->id)->count());
        $this->assertSame(0, UserWebPushPrivatePeerMute::query()->where('user_id', $user->id)->count());
    }

    public function test_invalid_room_id_returns_422(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/push/notification-settings', [
                'muted_room_ids' => [999999991],
            ])
            ->assertUnprocessable();
    }

    public function test_cannot_mute_self_as_private_peer(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/push/notification-settings', [
                'muted_private_peer_ids' => [(int) $user->id],
            ])
            ->assertUnprocessable();
    }
}

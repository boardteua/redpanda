<?php

namespace Tests\Feature;

use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSettingsApiTest extends TestCase
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

    public function test_guest_can_get_chat_settings_public_slice(): void
    {
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/settings')
            ->assertOk()
            ->assertJsonPath('data.room_create_min_public_messages', 100)
            ->assertJsonPath('data.public_message_count_scope', ChatSetting::SCOPE_ALL_PUBLIC_ROOMS)
            ->assertJsonPath('data.message_count_room_id', null);
    }

    public function test_non_admin_patch_returns_403(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'room_create_min_public_messages' => 10,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_patch_threshold_and_scope(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'room_create_min_public_messages' => 42,
                'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
            ])
            ->assertOk()
            ->assertJsonPath('data.room_create_min_public_messages', 42)
            ->assertJsonPath('data.public_message_count_scope', ChatSetting::SCOPE_DEFAULT_ROOM_ONLY);

        $this->assertSame(42, ChatSetting::current()->room_create_min_public_messages);
    }

    public function test_switching_scope_to_all_public_rooms_clears_room_id(): void
    {
        $admin = User::factory()->admin()->create();
        $room = Room::query()->create([
            'room_name' => 'Public A',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $row = ChatSetting::current();
        $row->update([
            'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
            'message_count_room_id' => $room->room_id,
        ]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            ])
            ->assertOk()
            ->assertJsonPath('data.message_count_room_id', null);

        $this->assertNull(ChatSetting::current()->message_count_room_id);
    }

    public function test_unauthenticated_get_returns_401(): void
    {
        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/settings')
            ->assertUnauthorized();
    }

    public function test_admin_cannot_set_message_count_room_to_non_public_room(): void
    {
        $admin = User::factory()->admin()->create();
        $vipRoom = Room::query()->create([
            'room_name' => 'VIP only',
            'topic' => null,
            'access' => Room::ACCESS_VIP,
        ]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
                'message_count_room_id' => $vipRoom->room_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message_count_room_id']);
    }
}

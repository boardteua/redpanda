<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomCreateApiTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedPublicChatMessage(Room $room, User $author, array $overrides = []): ChatMessage
    {
        $now = (int) ($overrides['post_date'] ?? time());

        return ChatMessage::query()->create(array_merge([
            'user_id' => $author->id,
            'post_date' => $now,
            'post_time' => date('H:i', $now),
            'post_user' => $author->user_name,
            'post_message' => 'seeded',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ], $overrides));
    }

    public function test_guest_post_room_returns_403(): void
    {
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', [
                'room_name' => 'Нова',
            ])
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Гості не можуть створювати кімнати.']);
    }

    public function test_registered_user_insufficient_public_messages_returns_403_with_code(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 2,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->seedPublicChatMessage($public, $user);
        $this->seedPublicChatMessage($public, $user);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', [
                'room_name' => 'Нова кімната',
                'topic' => 'Опис',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'room_create_insufficient_messages');
    }

    public function test_registered_user_above_threshold_creates_public_room(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 2,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->seedPublicChatMessage($public, $user);
        $this->seedPublicChatMessage($public, $user);
        $this->seedPublicChatMessage($public, $user);

        $response = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', [
                'room_name' => 'Кімната з тесту',
                'topic' => 'Короткий опис',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.room_name', 'Кімната з тесту')
            ->assertJsonPath('data.topic', 'Короткий опис')
            ->assertJsonPath('data.access', Room::ACCESS_PUBLIC);

        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Кімната з тесту',
            'topic' => 'Короткий опис',
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $user->id,
        ]);
    }

    public function test_vip_creates_room_without_message_threshold(): void
    {
        Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $vip = User::factory()->vip()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 99999,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', ['room_name' => 'VIP room'])
            ->assertCreated()
            ->assertJsonPath('data.room_name', 'VIP room');
    }

    public function test_moderator_creates_room_without_message_threshold(): void
    {
        Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $mod = User::factory()->moderator()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 99999,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', ['room_name' => 'Mod room'])
            ->assertCreated();
    }

    public function test_default_room_only_scope_counts_single_room(): void
    {
        $roomA = Room::query()->create([
            'room_name' => 'A',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $roomB = Room::query()->create([
            'room_name' => 'B',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
            'message_count_room_id' => $roomA->room_id,
        ]);

        $this->seedPublicChatMessage($roomB, $user);
        $this->seedPublicChatMessage($roomB, $user);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', ['room_name' => 'Fail'])
            ->assertForbidden()
            ->assertJsonPath('code', 'room_create_insufficient_messages');

        $this->seedPublicChatMessage($roomA, $user);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', ['room_name' => 'OK'])
            ->assertCreated()
            ->assertJsonPath('data.room_name', 'OK');
    }

    public function test_deleted_public_messages_not_counted(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 1,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->seedPublicChatMessage($public, $user, ['post_deleted_at' => time()]);
        $this->seedPublicChatMessage($public, $user, ['post_deleted_at' => time()]);
        $this->seedPublicChatMessage($public, $user, ['post_deleted_at' => time()]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms', ['room_name' => 'X'])
            ->assertForbidden();
    }

    public function test_auth_user_includes_can_create_room(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $user = User::factory()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.can_create_room', false);

        $this->seedPublicChatMessage($public, $user);

        $this->from(config('app.url'))
            ->actingAs($user->fresh(), 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.can_create_room', true);
    }
}

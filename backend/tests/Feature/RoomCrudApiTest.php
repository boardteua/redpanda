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

class RoomCrudApiTest extends TestCase
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

    public function test_guest_patch_room_returns_403(): void
    {
        $guest = User::factory()->guest()->create();
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, ['room_name' => 'X'])
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Гості не можуть змінювати кімнати.']);
    }

    public function test_stranger_cannot_patch_room_details(): void
    {
        Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Mine',
            'topic' => 't',
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $owner->id,
        ]);
        $other = User::factory()->create();
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($other, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, ['room_name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_creator_can_patch_room_name_and_topic(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Mine',
            'topic' => 'old',
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $owner->id,
        ]);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, [
                'room_name' => 'Нова назва',
                'topic' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.room_name', 'Нова назва')
            ->assertJsonPath('data.topic', null);

        $this->assertDatabaseHas('rooms', [
            'room_id' => $room->room_id,
            'room_name' => 'Нова назва',
            'topic' => null,
        ]);
    }

    public function test_creator_cannot_patch_access(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Mine',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $owner->id,
        ]);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, ['access' => Room::ACCESS_VIP])
            ->assertForbidden();
    }

    public function test_moderator_can_patch_access(): void
    {
        $mod = User::factory()->moderator()->create();
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, ['access' => Room::ACCESS_VIP])
            ->assertOk()
            ->assertJsonPath('data.access', Room::ACCESS_VIP);
    }

    public function test_moderator_can_patch_legacy_room_without_creator(): void
    {
        $mod = User::factory()->moderator()->create();
        $room = Room::query()->create([
            'room_name' => 'Legacy',
            'topic' => 'x',
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, ['room_name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.room_name', 'Renamed');
    }

    public function test_registered_user_cannot_patch_legacy_room_without_creator(): void
    {
        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Legacy',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => null,
        ]);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id, ['room_name' => 'No'])
            ->assertForbidden();
    }

    public function test_creator_deletes_empty_room(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Empty',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $owner->id,
        ]);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$room->room_id)
            ->assertNoContent();

        $this->assertDatabaseMissing('rooms', ['room_id' => $room->room_id]);
    }

    public function test_delete_room_with_messages_returns_422(): void
    {
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Has msgs',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $owner->id,
        ]);
        $this->seedPublicChatMessage($room, $owner);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$room->room_id)
            ->assertStatus(422)
            ->assertJsonPath('code', 'room_has_messages');
    }

    public function test_stranger_cannot_delete_room(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'X',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
            'created_by_user_id' => $owner->id,
        ]);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($other, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$room->room_id)
            ->assertForbidden();
    }

    public function test_vip_cannot_rename_vip_room_without_being_creator(): void
    {
        $vip = User::factory()->vip()->create();
        $vipRoom = Room::query()->create([
            'room_name' => 'VIP only',
            'topic' => null,
            'access' => Room::ACCESS_VIP,
            'created_by_user_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$vipRoom->room_id, ['room_name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_non_vip_cannot_patch_vip_room_due_to_interact(): void
    {
        $nonVip = User::factory()->create(['vip' => false]);
        $vipRoom = Room::query()->create([
            'room_name' => 'VIP only',
            'topic' => null,
            'access' => Room::ACCESS_VIP,
        ]);
        ChatSetting::query()->update([
            'room_create_min_public_messages' => 0,
            'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            'message_count_room_id' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($nonVip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$vipRoom->room_id, ['room_name' => 'Hack'])
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Немає доступу до цієї кімнати.']);
    }

    public function test_list_rooms_includes_messages_count(): void
    {
        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'A',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $this->seedPublicChatMessage($room, $user);

        $payload = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->json('data');

        $row = collect($payload)->firstWhere('room_id', $room->room_id);
        $this->assertNotNull($row);
        $this->assertSame(1, $row['messages_count']);
    }
}

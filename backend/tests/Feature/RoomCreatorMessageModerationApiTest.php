<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomCreatorMessageModerationApiTest extends TestCase
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
            'post_color' => $author->resolveChatRole()->postColorClass(),
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ], $overrides));
    }

    public function test_room_creator_patches_other_user_message_in_own_room(): void
    {
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Owned',
            'topic' => null,
            'access' => 0,
            'created_by_user_id' => $owner->id,
        ]);
        $author = User::factory()->create();
        $msg = $this->seedPublicChatMessage($room, $author, ['post_message' => 'hello']);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id.'/messages/'.$msg->post_id, [
                'message' => 'moderated',
            ])
            ->assertOk()
            ->assertJsonPath('data.post_message', 'moderated')
            ->assertJsonPath('data.can_edit', true);
    }

    public function test_room_creator_cannot_patch_message_in_room_they_did_not_create(): void
    {
        $stranger = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Legacy',
            'topic' => null,
            'access' => 0,
            'created_by_user_id' => null,
        ]);
        $author = User::factory()->create();
        $msg = $this->seedPublicChatMessage($room, $author);

        $this->from(config('app.url'))
            ->actingAs($stranger, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id.'/messages/'.$msg->post_id, [
                'message' => 'nope',
            ])
            ->assertForbidden();
    }

    public function test_room_creator_cannot_patch_moderator_message_in_own_room(): void
    {
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Owned',
            'topic' => null,
            'access' => 0,
            'created_by_user_id' => $owner->id,
        ]);
        $mod = User::factory()->moderator()->create();
        $msg = $this->seedPublicChatMessage($room, $mod);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id.'/messages/'.$msg->post_id, [
                'message' => 'nope',
            ])
            ->assertForbidden();
    }

    public function test_room_creator_cannot_patch_admin_message_in_own_room(): void
    {
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Owned',
            'topic' => null,
            'access' => 0,
            'created_by_user_id' => $owner->id,
        ]);
        $admin = User::factory()->admin()->create();
        $msg = $this->seedPublicChatMessage($room, $admin);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$room->room_id.'/messages/'.$msg->post_id, [
                'message' => 'nope',
            ])
            ->assertForbidden();
    }

    public function test_room_creator_deletes_other_user_message_in_own_room(): void
    {
        $owner = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'Owned',
            'topic' => null,
            'access' => 0,
            'created_by_user_id' => $owner->id,
        ]);
        $author = User::factory()->create();
        $msg = $this->seedPublicChatMessage($room, $author);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$room->room_id.'/messages/'.$msg->post_id)
            ->assertOk()
            ->assertJsonPath('data.post_message', '');

        $this->assertNotNull(ChatMessage::query()->find($msg->post_id)?->post_deleted_at);
    }

    public function test_get_rooms_includes_created_by_me_and_is_room_moderator_for_owner(): void
    {
        $owner = User::factory()->create();
        Room::query()->create([
            'room_name' => 'Mine',
            'topic' => null,
            'access' => 0,
            'created_by_user_id' => $owner->id,
        ]);

        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonFragment([
                'room_name' => 'Mine',
                'created_by_me' => true,
                'is_room_moderator' => true,
            ]);
    }
}

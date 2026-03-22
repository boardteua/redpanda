<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlaggedMessagesModerationApiTest extends TestCase
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

    public function test_regular_user_cannot_list_flagged_messages(): void
    {
        $user = User::factory()->create(['user_rank' => User::RANK_USER]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/flagged-messages')
            ->assertForbidden();
    }

    public function test_moderator_lists_flagged_messages_ordered_by_flag_time(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Main',
            'topic' => null,
            'access' => 0,
        ]);
        $author = User::factory()->create();
        $mod = User::factory()->moderator()->create();

        $older = ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time() - 100,
            'post_time' => 't1',
            'post_user' => $author->user_name,
            'post_message' => 'older flagged',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => time() - 50,
        ]);
        $newer = ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time() - 90,
            'post_time' => 't2',
            'post_user' => $author->user_name,
            'post_message' => 'newer flagged',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => time() - 10,
        ]);
        ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time() - 80,
            'post_time' => 't3',
            'post_user' => $author->user_name,
            'post_message' => 'no flag',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/flagged-messages?per_page=10')
            ->assertOk()
            ->assertJsonPath('data.0.post_id', $newer->post_id)
            ->assertJsonPath('data.1.post_id', $older->post_id)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filter_by_room_id(): void
    {
        $r1 = Room::query()->create(['room_name' => 'A', 'topic' => null, 'access' => 0]);
        $r2 = Room::query()->create(['room_name' => 'B', 'topic' => null, 'access' => 0]);
        $author = User::factory()->create();
        $mod = User::factory()->moderator()->create();

        ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time(),
            'post_time' => 'x',
            'post_user' => $author->user_name,
            'post_message' => 'in A',
            'post_roomid' => $r1->room_id,
            'type' => 'public',
            'moderation_flag_at' => time(),
        ]);
        ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time(),
            'post_time' => 'y',
            'post_user' => $author->user_name,
            'post_message' => 'in B',
            'post_roomid' => $r2->room_id,
            'type' => 'public',
            'moderation_flag_at' => time(),
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/flagged-messages?room_id='.$r2->room_id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.post_roomid', $r2->room_id);
    }

    public function test_moderator_clears_flag(): void
    {
        $room = Room::query()->create(['room_name' => 'X', 'topic' => null, 'access' => 0]);
        $author = User::factory()->create();
        $mod = User::factory()->moderator()->create();
        $msg = ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time(),
            'post_time' => 'z',
            'post_user' => $author->user_name,
            'post_message' => 'flagged body',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => time(),
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/flagged-messages/{$msg->post_id}")
            ->assertOk()
            ->assertJsonPath('data.post_id', $msg->post_id)
            ->assertJsonPath('data.moderation_flag_at', null);

        $msg->refresh();
        $this->assertNull($msg->moderation_flag_at);
    }

    public function test_clear_flag_returns_422_when_not_flagged(): void
    {
        $room = Room::query()->create(['room_name' => 'Y', 'topic' => null, 'access' => 0]);
        $author = User::factory()->create();
        $mod = User::factory()->moderator()->create();
        $msg = ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time(),
            'post_time' => 'z',
            'post_user' => $author->user_name,
            'post_message' => 'plain',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => null,
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/flagged-messages/{$msg->post_id}")
            ->assertUnprocessable();
    }

    public function test_regular_user_cannot_clear_flag(): void
    {
        $room = Room::query()->create(['room_name' => 'Z', 'topic' => null, 'access' => 0]);
        $author = User::factory()->create();
        $user = User::factory()->create(['user_rank' => User::RANK_USER]);
        $msg = ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time(),
            'post_time' => 'z',
            'post_user' => $author->user_name,
            'post_message' => 'x',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => time(),
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/flagged-messages/{$msg->post_id}")
            ->assertForbidden();
    }

    public function test_deleted_message_in_queue_has_empty_snippet(): void
    {
        $room = Room::query()->create(['room_name' => 'D', 'topic' => null, 'access' => 0]);
        $author = User::factory()->create();
        $mod = User::factory()->moderator()->create();
        ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => time(),
            'post_time' => 'z',
            'post_user' => $author->user_name,
            'post_message' => 'secret text',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'moderation_flag_at' => time(),
            'post_deleted_at' => time(),
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/flagged-messages')
            ->assertOk()
            ->assertJsonPath('data.0.is_deleted', true)
            ->assertJsonPath('data.0.snippet', '');
    }
}

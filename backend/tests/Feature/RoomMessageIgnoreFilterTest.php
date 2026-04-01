<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomMessageIgnoreFilterTest extends TestCase
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

    private function seedPublicRoom(): Room
    {
        return Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
    }

    private function seedPublicMessage(Room $room, User $author, string $body): ChatMessage
    {
        $now = time();

        return ChatMessage::query()->create([
            'user_id' => $author->id,
            'post_date' => $now,
            'post_time' => date('H:i', $now),
            'post_user' => $author->user_name,
            'post_message' => $body,
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ]);
    }

    public function test_room_history_hides_public_messages_from_ignored_author(): void
    {
        $room = $this->seedPublicRoom();
        $viewer = User::factory()->create();
        $ignored = User::factory()->create();
        $other = User::factory()->create();

        $mIgnored = $this->seedPublicMessage($room, $ignored, 'from ignored');
        $mOther = $this->seedPublicMessage($room, $other, 'from other');

        UserIgnore::query()->create([
            'user_id' => $viewer->id,
            'ignored_user_id' => $ignored->id,
        ]);

        $this->actingAs($viewer, 'web');
        $rows = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$room->room_id.'/messages?limit=50')
            ->assertOk()
            ->json('data');

        $ids = collect($rows)->pluck('post_id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($mOther->post_id, $ids);
        $this->assertNotContains($mIgnored->post_id, $ids);
    }

    public function test_room_history_shows_ignored_authors_messages_to_unrelated_viewer(): void
    {
        $room = $this->seedPublicRoom();
        $viewer = User::factory()->create();
        $ignored = User::factory()->create();

        $mIgnored = $this->seedPublicMessage($room, $ignored, 'from ignored');

        UserIgnore::query()->create([
            'user_id' => $viewer->id,
            'ignored_user_id' => $ignored->id,
        ]);

        $stranger = User::factory()->create();
        $this->actingAs($stranger, 'web');
        $rows = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$room->room_id.'/messages?limit=50')
            ->assertOk()
            ->json('data');

        $ids = collect($rows)->pluck('post_id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($mIgnored->post_id, $ids);
    }

    public function test_room_history_shows_messages_after_ignore_removed(): void
    {
        $room = $this->seedPublicRoom();
        $viewer = User::factory()->create();
        $ignored = User::factory()->create();
        $mIgnored = $this->seedPublicMessage($room, $ignored, 'visible again');

        $row = UserIgnore::query()->create([
            'user_id' => $viewer->id,
            'ignored_user_id' => $ignored->id,
        ]);

        $this->actingAs($viewer, 'web');
        $idsWhenIgnored = collect(
            $this->from(config('app.url'))
                ->withHeaders($this->statefulHeaders())
                ->getJson('/api/v1/rooms/'.$room->room_id.'/messages?limit=50')
                ->assertOk()
                ->json('data'),
        )->pluck('post_id')->map(fn ($id) => (int) $id)->all();
        $this->assertNotContains($mIgnored->post_id, $idsWhenIgnored);

        $row->delete();

        $ids = collect(
            $this->from(config('app.url'))
                ->withHeaders($this->statefulHeaders())
                ->getJson('/api/v1/rooms/'.$room->room_id.'/messages?limit=50')
                ->json('data'),
        )->pluck('post_id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($mIgnored->post_id, $ids);
    }
}

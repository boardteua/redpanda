<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatArchiveApiTest extends TestCase
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
     * @return array{0: Room, 1: Room}
     */
    private function seedRooms(): array
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $registered = Room::query()->create([
            'room_name' => 'Registered only',
            'topic' => null,
            'access' => 1,
        ]);

        return [$public, $registered];
    }

    private function seedMessage(Room $room, User $user, string $body, int $ts = 1_700_000_000): ChatMessage
    {
        return ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => $ts,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => $body,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => sprintf('f0000000-0000-4000-8000-%012d', $ts),
        ]);
    }

    public function test_archive_excludes_inline_private_room_messages(): void
    {
        [$public] = $this->seedRooms();
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->seedMessage($public, $a, 'public only');
        ChatMessage::query()->create([
            'user_id' => $a->id,
            'post_date' => 1_700_000_099,
            'post_time' => '12:00',
            'post_user' => $a->user_name,
            'post_message' => 'inline secret',
            'post_color' => 'user',
            'post_roomid' => $public->room_id,
            'type' => 'inline_private',
            'post_target' => (string) $b->id,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => 'a2222222-2222-4222-8222-222222222222',
        ]);

        $this->from(config('app.url'))
            ->actingAs($a, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_message', 'public only');
    }

    public function test_archive_excludes_soft_deleted_public_messages(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $this->seedMessage($public, $user, 'still here', 1_700_000_050);
        ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => 1_700_000_051,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => '',
            'post_color' => 'user',
            'post_roomid' => $public->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'post_deleted_at' => time(),
            'client_message_id' => 'b3333333-3333-4333-8333-333333333333',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_message', 'still here');
    }

    public function test_archive_excludes_public_messages_from_ignored_authors(): void
    {
        [$public] = $this->seedRooms();
        $viewer = User::factory()->create();
        $ignored = User::factory()->create();

        $this->seedMessage($public, $viewer, 'mine', 1_700_000_060);
        $hidden = $this->seedMessage($public, $ignored, 'from blocked', 1_700_000_061);

        UserIgnore::query()->create([
            'user_id' => $viewer->id,
            'ignored_user_id' => $ignored->id,
        ]);

        $ids = collect(
            $this->from(config('app.url'))
                ->actingAs($viewer, 'web')
                ->withHeaders($this->statefulHeaders())
                ->getJson('/api/v1/archive/messages?per_page=10')
                ->assertOk()
                ->json('data'),
        )->pluck('post_id')->map(fn ($id) => (int) $id)->all();

        $this->assertNotContains($hidden->post_id, $ids);
        $this->assertGreaterThanOrEqual(1, count($ids));
    }

    public function test_unauthenticated_archive_returns_401(): void
    {
        $this->seedRooms();

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages')
            ->assertUnauthorized();
    }

    public function test_guest_archive_sees_only_public_room_messages(): void
    {
        [$public, $registered] = $this->seedRooms();
        $guest = User::factory()->guest()->create();
        $regUser = User::factory()->create();

        $this->seedMessage($public, $guest, 'in public');
        $this->seedMessage($registered, $regUser, 'in registered only', 1_700_000_001);

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_message', 'in public');
    }

    public function test_registered_user_archive_sees_all_accessible_rooms(): void
    {
        [$public, $registered] = $this->seedRooms();
        $user = User::factory()->create();

        $this->seedMessage($public, $user, 'a', 1_700_000_010);
        $this->seedMessage($registered, $user, 'b', 1_700_000_011);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.post_message', 'b')
            ->assertJsonPath('data.1.post_message', 'a');
    }

    public function test_room_filter_forbidden_for_guest_on_registered_room(): void
    {
        [$public, $registered] = $this->seedRooms();
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?room='.$registered->room_id)
            ->assertForbidden();
    }

    public function test_room_filter_unknown_returns_404(): void
    {
        $this->seedRooms();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?room=999999')
            ->assertNotFound();
    }

    public function test_search_matches_message_and_username(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $this->seedMessage($public, $user, 'hello banana world', 1_700_000_020);
        $other = User::factory()->create(['user_name' => 'zebra_finder']);
        $this->seedMessage($public, $other, 'other text', 1_700_000_021);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?q='.rawurlencode('banana'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_message', 'hello banana world');

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?q='.rawurlencode('zebra'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_user', 'zebra_finder');
    }

    public function test_pagination_meta_and_per_page_validation(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            $this->seedMessage($public, $user, 'm'.$i, 1_700_000_100 + $i);
        }

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?per_page=10&page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 15);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/archive/messages?per_page=99')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}

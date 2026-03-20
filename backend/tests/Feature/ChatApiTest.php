<?php

namespace Tests\Feature;

use App\Events\MessagePosted;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ChatApiTest extends TestCase
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

    public function test_unauthenticated_requests_receive_401(): void
    {
        $this->seedRooms();

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertUnauthorized();
    }

    public function test_unknown_room_messages_return_404(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/999999/messages')
            ->assertNotFound();
    }

    public function test_guest_room_list_excludes_registered_only_rooms(): void
    {
        [$public, $registered] = $this->seedRooms();

        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.room_id', $public->room_id);
    }

    public function test_registered_user_sees_all_rooms(): void
    {
        [$public, $registered] = $this->seedRooms();

        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_guest_cannot_read_messages_in_registered_only_room(): void
    {
        [$public, $registered] = $this->seedRooms();
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$registered->room_id.'/messages')
            ->assertForbidden();
    }

    public function test_post_dispatches_broadcast_only_for_new_message(): void
    {
        Bus::fake([BroadcastEvent::class]);

        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $clientId = 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380a33';

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'first',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof MessagePosted;
        });

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'duplicate body ignored',
                'client_message_id' => $clientId,
            ])
            ->assertOk();

        Bus::assertDispatchedTimes(BroadcastEvent::class, 1);
    }

    public function test_post_message_slash_me_and_idempotent_duplicate(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $clientId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $first = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => '/me says hi',
                'client_message_id' => $clientId,
            ]);

        $first->assertCreated()
            ->assertJsonPath('data.post_message', '*'.$user->user_name.' says hi*')
            ->assertJsonPath('meta.duplicate', false)
            ->assertJsonPath('meta.slash.recognized', true);

        $postId = $first->json('data.post_id');

        $second = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'different body',
                'client_message_id' => $clientId,
            ]);

        $second->assertOk()
            ->assertJsonPath('data.post_id', $postId)
            ->assertJsonPath('meta.duplicate', true)
            ->assertJsonPath('meta.slash.name', null)
            ->assertJsonPath('meta.slash.recognized', false);

        $this->assertSame(1, ChatMessage::query()->where('client_message_id', $clientId)->count());
    }

    public function test_same_client_id_in_different_room_returns_422(): void
    {
        [$public, $registered] = $this->seedRooms();
        $user = User::factory()->create();
        $clientId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22';

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'one',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$registered->room_id.'/messages', [
                'message' => 'two',
                'client_message_id' => $clientId,
            ])
            ->assertStatus(422);
    }

    public function test_validation_errors_on_post(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => '',
                'client_message_id' => 'not-a-uuid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message', 'client_message_id']);
    }

    public function test_messages_index_returns_chronological_page_and_cursor_meta(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        foreach (['a', 'b', 'c'] as $i => $suffix) {
            ChatMessage::query()->create([
                'user_id' => $user->id,
                'post_date' => 1000 + $i,
                'post_time' => '10:0'.$i,
                'post_user' => $user->user_name,
                'post_message' => 'm'.$suffix,
                'post_color' => 'user',
                'post_roomid' => $public->room_id,
                'type' => 'public',
                'post_target' => null,
                'avatar' => null,
                'file' => 0,
                'client_message_id' => 'c0000000-0000-4000-8000-00000000000'.($i + 1),
            ]);
        }

        $res = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$public->room_id.'/messages?limit=2');

        $res->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.post_message', 'mb')
            ->assertJsonPath('data.1.post_message', 'mc')
            ->assertJsonPath('meta.next_cursor', fn ($v) => $v !== null);
    }
}

<?php

namespace Tests\Feature;

use App\Events\MessageDeleted;
use App\Events\MessagePosted;
use App\Events\MessageUpdated;
use App\Events\PrivateMessageCreated;
use App\Events\RoomInlinePrivatePosted;
use App\Models\ChatMessage;
use App\Models\Image;
use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
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
            ->assertJsonPath('data.avatar', null)
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

    public function test_messages_index_includes_avatar_url_when_set(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => 2000,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => 'with avatar',
            'post_color' => 'user',
            'post_roomid' => $public->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => 'https://example.test/avatars/u1.png',
            'file' => 0,
            'client_message_id' => 'd0000000-0000-4000-8000-000000000001',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$public->room_id.'/messages?limit=10')
            ->assertOk()
            ->assertJsonPath('data.0.avatar', 'https://example.test/avatars/u1.png');
    }

    public function test_message_posted_broadcasts_on_presence_room_channel(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $m = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => 2001,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => 'hi',
            'post_color' => 'user',
            'post_roomid' => $public->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => '',
            'file' => 0,
            'client_message_id' => 'e0000000-0000-4000-8000-000000000001',
        ]);

        $channels = (new MessagePosted($m))->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PresenceChannel::class, $channels[0]);
        $this->assertSame('presence-room.'.$public->room_id, $channels[0]->name);
    }

    public function test_inline_private_hidden_from_third_user_in_room_feed(): void
    {
        [$public] = $this->seedRooms();
        $a = User::factory()->create(['user_name' => 'alice_pm']);
        $b = User::factory()->create(['user_name' => 'bob_pm']);
        $c = User::factory()->create(['user_name' => 'carol_pm']);

        ChatMessage::query()->create([
            'user_id' => $a->id,
            'post_date' => 3000,
            'post_time' => '12:00',
            'post_user' => $a->user_name,
            'post_message' => 'secret for bob',
            'post_color' => 'user',
            'post_roomid' => $public->room_id,
            'type' => 'inline_private',
            'post_target' => (string) $b->id,
            'avatar' => '',
            'file' => 0,
            'client_message_id' => 'f1111111-1111-4111-8111-111111111111',
        ]);

        $this->assertSame(1, ChatMessage::query()->where('post_roomid', $public->room_id)->count());

        Sanctum::actingAs($c);
        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$public->room_id.'/messages?limit=20')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($a);
        $resA = $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$public->room_id.'/messages?limit=20');
        $resA->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_message', 'secret for bob')
            ->assertJsonPath('data.0.type', 'inline_private')
            ->assertJsonPath('data.0.recipient_user_id', $b->id);

        Sanctum::actingAs($b);
        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$public->room_id.'/messages?limit=20')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.post_message', 'secret for bob');
    }

    public function test_post_msg_inline_private_dispatches_user_channel_broadcast_not_room(): void
    {
        Bus::fake([BroadcastEvent::class]);

        [$public] = $this->seedRooms();
        $a = User::factory()->create(['user_name' => 'sender_x']);
        $b = User::factory()->create(['user_name' => 'recv_x']);
        $clientId = 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a01';

        $this->from(config('app.url'))
            ->actingAs($a, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => '/msg recv_x hello-inline',
                'client_message_id' => $clientId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'inline_private')
            ->assertJsonPath('data.recipient_user_id', $b->id)
            ->assertJsonPath('data.post_message', 'hello-inline')
            ->assertJsonPath('meta.slash.name', 'msg')
            ->assertJsonPath('meta.slash.recognized', true);

        $this->assertDatabaseHas('private_messages', [
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'body' => 'hello-inline',
            'client_message_id' => $clientId,
        ]);

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof RoomInlinePrivatePosted;
        });
        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof PrivateMessageCreated;
        });
        Bus::assertNotDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof MessagePosted;
        });
    }

    public function test_inline_msg_rejects_client_id_already_used_by_private_api(): void
    {
        [$public] = $this->seedRooms();
        $a = User::factory()->create(['user_name' => 'inline_dup_a']);
        $b = User::factory()->create(['user_name' => 'inline_dup_b']);
        $clientId = 'c3eebc99-9c0b-4ef8-bb6d-6bb9bd380a03';

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/private/peers/'.$b->id.'/messages', [
            'message' => 'from panel',
            'client_message_id' => $clientId,
        ])->assertCreated();

        $this->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
            'message' => '/msg inline_dup_b overlap',
            'client_message_id' => $clientId,
        ])->assertStatus(422);
    }

    public function test_post_msg_unknown_peer_returns_422(): void
    {
        [$public] = $this->seedRooms();
        $a = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($a, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => '/msg no_such_user_xyz body',
                'client_message_id' => 'b2eebc99-9c0b-4ef8-bb6d-6bb9bd380a02',
            ])
            ->assertStatus(422);
    }

    public function test_post_accepts_message_style_and_returns_in_feed(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $clientId = 'f4eebc99-9c0b-4ef8-bb6d-6bb9bd380a01';

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'styled hello',
                'client_message_id' => $clientId,
                'style' => [
                    'bold' => true,
                    'italic' => true,
                    'underline' => false,
                    'bg' => 'amber',
                    'fg' => null,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.post_style.bold', true)
            ->assertJsonPath('data.post_style.italic', true)
            ->assertJsonPath('data.post_style.bg', 'amber')
            ->assertJsonPath('data.post_style.fg', null);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$public->room_id.'/messages?limit=10')
            ->assertOk()
            ->assertJsonPath('data.0.post_style.bg', 'amber');
    }

    public function test_post_rejects_invalid_message_style_bg(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'x',
                'client_message_id' => 'a5eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
                'style' => [
                    'bold' => false,
                    'bg' => 'not-a-preset',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['style.bg']);
    }

    public function test_post_rejects_style_with_both_bg_and_fg(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => 'x',
                'client_message_id' => 'b6eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
                'style' => [
                    'bold' => true,
                    'bg' => 'mint',
                    'fg' => 'blue',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['style.fg']);
    }

    public function test_post_message_stores_angle_brackets_as_literal_text(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $payload = '<img src=x onerror=alert(1)> hi & "quotes"';
        $clientId = 'c7eebc99-9c0b-4ef8-bb6d-6bb9bd380a01';

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => $payload,
                'client_message_id' => $clientId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.post_message', $payload);
    }

    public function test_post_message_rejects_over_max_length_for_registered_user(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => str_repeat('a', 4001),
                'client_message_id' => 'd7eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_post_message_rejects_over_max_length_for_guest(): void
    {
        [$public] = $this->seedRooms();
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$public->room_id.'/messages', [
                'message' => str_repeat('b', 2001),
                'client_message_id' => 'e7eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_patch_message_owner_updates_text_and_broadcasts(): void
    {
        Bus::fake([BroadcastEvent::class]);

        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user, ['post_message' => 'alpha']);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'beta',
            ])
            ->assertOk()
            ->assertJsonPath('data.post_message', 'beta')
            ->assertJsonPath('data.can_edit', true);

        $this->assertNotNull(ChatMessage::query()->find($msg->post_id)?->post_edited_at);

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof MessageUpdated;
        });
    }

    public function test_patch_message_with_image_preserves_file_and_allows_empty_text(): void
    {
        Bus::fake([BroadcastEvent::class]);

        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $image = Image::query()->create([
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'disk_path' => 'chat/test/'.Str::random(8).'.png',
            'file_name' => 'x.png',
            'mime' => 'image/png',
            'size_bytes' => 100,
            'date_sent' => time(),
        ]);
        $msg = $this->seedPublicChatMessage($public, $user, [
            'post_message' => 'caption',
            'file' => $image->id,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'new caption',
            ])
            ->assertOk()
            ->assertJsonPath('data.file', $image->id)
            ->assertJsonPath('data.post_message', 'new caption');

        $this->assertSame($image->id, (int) ChatMessage::query()->find($msg->post_id)?->file);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => '   ',
            ])
            ->assertOk()
            ->assertJsonPath('data.file', $image->id)
            ->assertJsonPath('data.post_message', '');

        $this->assertSame($image->id, (int) ChatMessage::query()->find($msg->post_id)?->file);

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof MessageUpdated;
        });
    }

    public function test_patch_message_guest_forbidden(): void
    {
        [$public] = $this->seedRooms();
        $author = User::factory()->create();
        $guest = User::factory()->guest()->create();
        $msg = $this->seedPublicChatMessage($public, $author);

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_patch_message_cannot_edit_other_user(): void
    {
        [$public] = $this->seedRooms();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $a);

        $this->from(config('app.url'))
            ->actingAs($b, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'hijack',
            ])
            ->assertForbidden();
    }

    public function test_patch_message_plain_user_forbidden_after_edit_window(): void
    {
        Config::set('chat.message_edit_window_hours', 1);

        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $oldTs = time() - 7200;
        $msg = $this->seedPublicChatMessage($public, $user, ['post_date' => $oldTs]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'too late',
            ])
            ->assertForbidden();
    }

    public function test_patch_message_vip_can_edit_after_edit_window(): void
    {
        Config::set('chat.message_edit_window_hours', 1);

        [$public] = $this->seedRooms();
        $vip = User::factory()->vip()->create();
        $oldTs = time() - 7200;
        $msg = $this->seedPublicChatMessage($public, $vip, ['post_date' => $oldTs]);

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'vip ok',
            ])
            ->assertOk()
            ->assertJsonPath('data.post_message', 'vip ok');
    }

    public function test_patch_message_moderator_edits_user_message(): void
    {
        [$public] = $this->seedRooms();
        $mod = User::factory()->moderator()->create();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'mod cleaned',
            ])
            ->assertOk()
            ->assertJsonPath('data.post_message', 'mod cleaned');
    }

    public function test_patch_message_moderator_cannot_edit_admin_authored_message(): void
    {
        [$public] = $this->seedRooms();
        $mod = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();
        $msg = $this->seedPublicChatMessage($public, $admin);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'nope',
            ])
            ->assertForbidden();
    }

    public function test_patch_message_admin_edits_admin_authored_message(): void
    {
        [$public] = $this->seedRooms();
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $msg = $this->seedPublicChatMessage($public, $adminA);

        $this->from(config('app.url'))
            ->actingAs($adminB, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'admin fixed',
            ])
            ->assertOk()
            ->assertJsonPath('data.post_message', 'admin fixed');
    }

    public function test_patch_message_wrong_room_returns_404(): void
    {
        [$public, $registered] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$registered->room_id.'/messages/'.$msg->post_id, [
                'message' => 'wrong',
            ])
            ->assertNotFound();
    }

    public function test_patch_inline_private_message_forbidden(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user, [
            'type' => 'inline_private',
            'post_target' => '1',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'nope',
            ])
            ->assertForbidden();
    }

    public function test_delete_message_owner_soft_deletes_and_broadcasts(): void
    {
        Bus::fake([BroadcastEvent::class]);

        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user, ['post_message' => 'bye']);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertOk()
            ->assertJsonPath('data.post_message', '')
            ->assertJsonPath('data.can_edit', false)
            ->assertJsonPath('data.can_delete', false);

        $fresh = ChatMessage::query()->find($msg->post_id);
        $this->assertNotNull($fresh?->post_deleted_at);
        $this->assertSame('', $fresh->post_message);
        $this->assertSame(0, (int) $fresh->file);

        Bus::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $job) {
            return $job->event instanceof MessageDeleted;
        });
    }

    public function test_delete_message_second_time_idempotent_no_broadcast(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user, ['post_message' => 'x']);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertOk();

        Bus::fake([BroadcastEvent::class]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertOk()
            ->assertJsonPath('data.can_delete', false);

        Bus::assertNotDispatched(BroadcastEvent::class);
    }

    public function test_delete_message_guest_forbidden(): void
    {
        [$public] = $this->seedRooms();
        $author = User::factory()->create();
        $guest = User::factory()->guest()->create();
        $msg = $this->seedPublicChatMessage($public, $author);

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertForbidden();
    }

    public function test_delete_message_cannot_delete_other_user(): void
    {
        [$public] = $this->seedRooms();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $a);

        $this->from(config('app.url'))
            ->actingAs($b, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertForbidden();
    }

    public function test_delete_message_plain_user_forbidden_after_edit_window(): void
    {
        Config::set('chat.message_edit_window_hours', 1);

        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $oldTs = time() - 7200;
        $msg = $this->seedPublicChatMessage($public, $user, ['post_date' => $oldTs]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertForbidden();
    }

    public function test_delete_message_vip_can_delete_after_edit_window(): void
    {
        Config::set('chat.message_edit_window_hours', 1);

        [$public] = $this->seedRooms();
        $vip = User::factory()->vip()->create();
        $oldTs = time() - 7200;
        $msg = $this->seedPublicChatMessage($public, $vip, ['post_date' => $oldTs]);

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertOk()
            ->assertJsonPath('data.post_message', '');
    }

    public function test_delete_message_moderator_deletes_user_message(): void
    {
        [$public] = $this->seedRooms();
        $mod = User::factory()->moderator()->create();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertOk();
    }

    public function test_delete_message_moderator_cannot_delete_admin_authored_message(): void
    {
        [$public] = $this->seedRooms();
        $mod = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();
        $msg = $this->seedPublicChatMessage($public, $admin);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertForbidden();
    }

    public function test_delete_message_admin_deletes_admin_authored_message(): void
    {
        [$public] = $this->seedRooms();
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $msg = $this->seedPublicChatMessage($public, $adminA);

        $this->from(config('app.url'))
            ->actingAs($adminB, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertOk();
    }

    public function test_delete_message_wrong_room_returns_404(): void
    {
        [$public, $registered] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$registered->room_id.'/messages/'.$msg->post_id)
            ->assertNotFound();
    }

    public function test_delete_inline_private_message_forbidden(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user, [
            'type' => 'inline_private',
            'post_target' => '1',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->deleteJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id)
            ->assertForbidden();
    }

    public function test_patch_deleted_message_forbidden(): void
    {
        [$public] = $this->seedRooms();
        $user = User::factory()->create();
        $msg = $this->seedPublicChatMessage($public, $user, [
            'post_deleted_at' => time(),
            'post_message' => '',
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/rooms/'.$public->room_id.'/messages/'.$msg->post_id, [
                'message' => 'nope',
            ])
            ->assertForbidden();
    }
}

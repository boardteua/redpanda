<?php

namespace Tests\Feature;

use App\Events\PrivateMessageCreated;
use App\Events\PrivateThreadCleared;
use App\Models\PrivateMessage;
use App\Models\PrivateMessageReadState;
use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrivateMessageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_send_private_message_dispatches_broadcast_and_persists(): void
    {
        Event::fake([PrivateMessageCreated::class]);

        $a = User::factory()->create(['user_name' => 'alice']);
        $b = User::factory()->create(['user_name' => 'bob']);

        $clientId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a01';

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/private/peers/'.$b->id.'/messages', [
            'message' => 'Привіт у ПП',
            'client_message_id' => $clientId,
        ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Привіт у ПП');

        $this->assertDatabaseHas('private_messages', [
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'client_message_id' => $clientId,
        ]);

        Event::assertDispatched(PrivateMessageCreated::class);
    }

    public function test_cannot_message_self(): void
    {
        $a = User::factory()->create();

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/private/peers/'.$a->id.'/messages', [
            'message' => 'x',
            'client_message_id' => 'c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ])
            ->assertStatus(422);
    }

    public function test_ignore_blocks_private_in_both_directions(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        UserIgnore::query()->create([
            'user_id' => $b->id,
            'ignored_user_id' => $a->id,
        ]);

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/private/peers/'.$b->id.'/messages', [
            'message' => 'spam',
            'client_message_id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ])
            ->assertForbidden();

        Sanctum::actingAs($b);
        $this->postJson('/api/v1/private/peers/'.$a->id.'/messages', [
            'message' => 'back',
            'client_message_id' => 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
        ])
            ->assertForbidden();
    }

    public function test_conversations_lists_peer_and_last_message(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'body' => 'one',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'd3eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'two',
            'sent_at' => time() + 1,
            'sent_time' => '12:01',
            'client_message_id' => 'e4eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/private/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.peer.user_name', $b->user_name)
            ->assertJsonPath('data.0.last_message.body', 'two')
            ->assertJsonPath('data.0.unread_count', 1)
            ->assertJsonPath('meta.total_private_unread', 1);
    }

    public function test_conversations_counts_incoming_unread_and_meta_total(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'unread-one',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'f1eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'unread-two',
            'sent_at' => time() + 1,
            'sent_time' => '12:01',
            'client_message_id' => 'f2eebc99-9c0b-4ef8-bb6d-6bb9bd380a02',
        ]);

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/private/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 2)
            ->assertJsonPath('meta.total_private_unread', 2);
    }

    public function test_conversations_unread_per_peer_and_total_with_multiple_peers(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $d = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'body' => 'a-to-b',
            'sent_at' => time(),
            'sent_time' => '10:00',
            'client_message_id' => '10000000-0000-4000-8000-000000000001',
        ]);
        $bFirst = PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'b-first',
            'sent_at' => time() + 1,
            'sent_time' => '10:01',
            'client_message_id' => '10000000-0000-4000-8000-000000000002',
        ]);
        PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'b-second',
            'sent_at' => time() + 2,
            'sent_time' => '10:02',
            'client_message_id' => '10000000-0000-4000-8000-000000000003',
        ]);
        PrivateMessageReadState::query()->create([
            'user_id' => $a->id,
            'peer_id' => $b->id,
            'last_read_incoming_message_id' => $bFirst->id,
        ]);

        PrivateMessage::query()->create([
            'sender_id' => $c->id,
            'recipient_id' => $a->id,
            'body' => 'c-only',
            'sent_at' => time() + 3,
            'sent_time' => '10:03',
            'client_message_id' => '10000000-0000-4000-8000-000000000004',
        ]);

        PrivateMessage::query()->create([
            'sender_id' => $a->id,
            'recipient_id' => $d->id,
            'body' => 'a-to-d',
            'sent_at' => time() + 4,
            'sent_time' => '10:04',
            'client_message_id' => '10000000-0000-4000-8000-000000000005',
        ]);
        $dFirst = PrivateMessage::query()->create([
            'sender_id' => $d->id,
            'recipient_id' => $a->id,
            'body' => 'd-first',
            'sent_at' => time() + 5,
            'sent_time' => '10:05',
            'client_message_id' => '10000000-0000-4000-8000-000000000006',
        ]);
        PrivateMessage::query()->create([
            'sender_id' => $d->id,
            'recipient_id' => $a->id,
            'body' => 'd-second',
            'sent_at' => time() + 6,
            'sent_time' => '10:06',
            'client_message_id' => '10000000-0000-4000-8000-000000000007',
        ]);
        PrivateMessageReadState::query()->create([
            'user_id' => $a->id,
            'peer_id' => $d->id,
            'last_read_incoming_message_id' => $dFirst->id,
        ]);

        Sanctum::actingAs($a);
        $res = $this->getJson('/api/v1/private/conversations')->assertOk();

        $this->assertSame(3, $res->json('meta.total_private_unread'));
        $rows = $res->json('data');
        $this->assertCount(3, $rows);
        $byPeer = [];
        foreach ($rows as $row) {
            $byPeer[$row['peer']['id']] = $row['unread_count'];
        }
        $this->assertSame(1, $byPeer[$b->id]);
        $this->assertSame(1, $byPeer[$c->id]);
        $this->assertSame(1, $byPeer[$d->id]);
    }

    public function test_fetching_private_thread_marks_incoming_read(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'hello',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'a3eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/private/peers/'.$b->id.'/messages')
            ->assertOk();

        $this->getJson('/api/v1/private/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 0)
            ->assertJsonPath('meta.total_private_unread', 0);
    }

    public function test_post_read_marks_incoming_without_full_history_load(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $b->id,
            'recipient_id' => $a->id,
            'body' => 'ping',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'b4eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/private/peers/'.$b->id.'/read')
            ->assertOk()
            ->assertJsonPath('meta.ok', true);

        $this->getJson('/api/v1/private/conversations')
            ->assertOk()
            ->assertJsonPath('meta.total_private_unread', 0);
    }

    public function test_users_lookup_by_name(): void
    {
        $a = User::factory()->create(['user_name' => 'lookupme']);
        $self = User::factory()->create();

        Sanctum::actingAs($self);
        $this->getJson('/api/v1/users/lookup?name=lookupme')
            ->assertOk()
            ->assertJsonPath('data.id', $a->id);

        $this->getJson('/api/v1/users/lookup?name=nobody_here_xyz')
            ->assertNotFound();
    }

    public function test_destroy_thread_clears_messages_read_state_and_dispatches_event(): void
    {
        Event::fake([PrivateThreadCleared::class]);

        $a = User::factory()->create();
        $b = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'body' => 'x',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'c5eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        PrivateMessageReadState::query()->create([
            'user_id' => $a->id,
            'peer_id' => $b->id,
            'last_read_incoming_message_id' => 1,
        ]);

        Sanctum::actingAs($a);
        $this->deleteJson('/api/v1/private/peers/'.$b->id.'/thread')
            ->assertOk()
            ->assertJsonPath('meta.ok', true)
            ->assertJsonPath('meta.cleared_peer_id', $b->id);

        $this->assertSame(0, PrivateMessage::query()->count());
        $this->assertSame(0, PrivateMessageReadState::query()->count());

        Event::assertDispatched(PrivateThreadCleared::class);
    }

    public function test_destroy_thread_forbidden_when_pair_is_blocked_by_ignore(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        PrivateMessage::query()->create([
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'body' => 'x',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'c6eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        UserIgnore::query()->create([
            'user_id' => $b->id,
            'ignored_user_id' => $a->id,
        ]);

        Sanctum::actingAs($a);
        $this->deleteJson('/api/v1/private/peers/'.$b->id.'/thread')
            ->assertForbidden();

        $this->assertSame(1, PrivateMessage::query()->count());
    }
}

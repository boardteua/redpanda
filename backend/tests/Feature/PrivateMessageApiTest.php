<?php

namespace Tests\Feature;

use App\Events\PrivateMessageCreated;
use App\Models\PrivateMessage;
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
}

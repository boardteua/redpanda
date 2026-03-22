<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FriendApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_request_accept_and_list_friends(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/friends/'.$b->id)
            ->assertStatus(201);

        $this->assertDatabaseHas('friendships', [
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        Sanctum::actingAs($b);
        $this->getJson('/api/v1/friends/requests/incoming')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson('/api/v1/friends/'.$a->id.'/accept')
            ->assertOk();

        Sanctum::actingAs($a);
        $this->getJson('/api/v1/friends')
            ->assertOk()
            ->assertJsonPath('data.0.user.id', $b->id);
    }

    public function test_reverse_pending_returns_409(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Friendship::query()->create([
            'requester_id' => $b->id,
            'addressee_id' => $a->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/friends/'.$b->id)
            ->assertStatus(409);
    }

    public function test_cannot_send_friend_request_to_guest(): void
    {
        $a = User::factory()->create();
        $guest = User::factory()->guest()->create();

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/friends/'.$guest->id)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Неможливо додати гостя до друзів.']);
    }

    public function test_destroy_removes_accepted_friendship(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Friendship::query()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        Sanctum::actingAs($a);
        $this->deleteJson('/api/v1/friends/'.$b->id)
            ->assertOk()
            ->assertJsonFragment(['message' => 'Користувача прибрано з друзів.']);

        $this->assertDatabaseMissing('friendships', [
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
        ]);
    }

    public function test_destroy_cancels_outgoing_pending(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Friendship::query()->create([
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        Sanctum::actingAs($a);
        $this->deleteJson('/api/v1/friends/'.$b->id)
            ->assertOk()
            ->assertJsonFragment(['message' => 'Запит скасовано.']);

        $this->assertDatabaseMissing('friendships', [
            'requester_id' => $a->id,
            'addressee_id' => $b->id,
        ]);
    }

    public function test_destroy_returns_404_when_no_row(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Sanctum::actingAs($a);
        $this->deleteJson('/api/v1/friends/'.$b->id)
            ->assertStatus(404);
    }
}

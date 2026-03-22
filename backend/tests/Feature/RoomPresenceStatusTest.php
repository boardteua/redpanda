<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Services\Chat\RoomPresenceStatusCache;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomPresenceStatusTest extends TestCase
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

    public function test_guest_cannot_post_presence_status_to_registered_only_room(): void
    {
        $registered = Room::query()->create([
            'room_name' => 'Registered only',
            'topic' => null,
            'access' => 1,
        ]);
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$registered->room_id}/presence-status", ['status' => 'online'])
            ->assertForbidden();
    }

    public function test_index_returns_cached_peer_statuses(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        RoomPresenceStatusCache::put((int) $public->room_id, (int) $bob->id, 'inactive');

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/presence-statuses?user_ids=".$bob->id)
            ->assertOk()
            ->assertJsonPath('data.'.(string) $bob->id, 'inactive');
    }

    public function test_index_requires_user_ids(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/presence-statuses")
            ->assertUnprocessable();
    }

    public function test_store_persists_status_and_allows_transitions(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$public->room_id}/presence-status", ['status' => 'away'])
            ->assertOk()
            ->assertJsonPath('data.status', 'away');

        $this->assertSame(
            'away',
            RoomPresenceStatusCache::get((int) $public->room_id, (int) $user->id),
        );

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$public->room_id}/presence-status", ['status' => 'away'])
            ->assertOk();

        $this->assertSame(
            'away',
            RoomPresenceStatusCache::get((int) $public->room_id, (int) $user->id),
        );

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$public->room_id}/presence-status", ['status' => 'online'])
            ->assertOk();

        $this->assertSame(
            'online',
            RoomPresenceStatusCache::get((int) $public->room_id, (int) $user->id),
        );
    }

    public function test_store_invalid_status_422(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$public->room_id}/presence-status", ['status' => 'nope'])
            ->assertUnprocessable();
    }
}

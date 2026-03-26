<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\Chat\RoomPresenceStatusCache;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomUserProfileApiTest extends TestCase
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

    public function test_guest_viewer_forbidden(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $guest = User::factory()->guest()->create();
        $bob = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$bob->id}/profile-card")
            ->assertForbidden();
    }

    public function test_registered_viewer_without_room_access_forbidden(): void
    {
        $vipRoom = Room::query()->create([
            'room_name' => 'VIP',
            'topic' => null,
            'access' => 2,
        ]);
        $alice = User::factory()->create(['vip' => false]);
        $bob = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$vipRoom->room_id}/users/{$bob->id}/profile-card")
            ->assertForbidden();
    }

    public function test_forbidden_when_stranger_without_presence_friendship_or_private(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$bob->id}/profile-card")
            ->assertForbidden();
    }

    public function test_peer_card_hides_email_and_hidden_profile_fields_for_stranger(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create(['email' => 'alice@example.com']);
        $bob = User::factory()->create([
            'email' => 'bob@example.com',
            'profile_country' => 'PL',
            'profile_occupation' => 'Dev',
            'profile_occupation_hidden' => true,
            'profile_about' => 'Secret',
            'profile_about_hidden' => true,
            'social_links' => [
                'facebook' => '',
                'instagram' => '',
                'telegram' => 'https://t.me/bob',
                'twitter' => '',
                'youtube' => '',
                'tiktok' => '',
                'discord' => '',
                'website' => '',
            ],
        ]);

        RoomPresenceStatusCache::put((int) $room->room_id, (int) $bob->id, 'online');

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$bob->id}/profile-card")
            ->assertOk()
            ->assertJsonPath('data.user_name', $bob->user_name)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.profile.country', 'PL')
            ->assertJsonPath('data.profile.occupation', null)
            ->assertJsonPath('data.profile.about', null)
            ->assertJsonPath('data.social_links.telegram', 'https://t.me/bob');
    }

    public function test_allowed_for_accepted_friend_without_room_presence_cache(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create();
        $bob = User::factory()->create(['user_name' => 'bobfriend']);

        Friendship::query()->create([
            'requester_id' => $alice->id,
            'addressee_id' => $bob->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$bob->id}/profile-card")
            ->assertOk()
            ->assertJsonPath('data.user_name', 'bobfriend');
    }

    public function test_allowed_for_private_peer_without_room_presence_cache(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create();
        $bob = User::factory()->create(['user_name' => 'bobpm']);

        PrivateMessage::query()->create([
            'sender_id' => $alice->id,
            'recipient_id' => $bob->id,
            'body' => 'hi',
            'sent_at' => time(),
            'sent_time' => '12:00',
            'client_message_id' => 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a01',
        ]);

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$bob->id}/profile-card")
            ->assertOk()
            ->assertJsonPath('data.user_name', 'bobpm');
    }

    public function test_moderator_may_fetch_without_presence_cache(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $mod = User::factory()->create(['user_rank' => User::RANK_MODERATOR]);
        $bob = User::factory()->create(['user_name' => 'stranger']);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$bob->id}/profile-card")
            ->assertOk()
            ->assertJsonPath('data.user_name', 'stranger');
    }

    public function test_self_may_fetch_without_presence_cache(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create(['user_name' => 'selfuser']);

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$room->room_id}/users/{$alice->id}/profile-card")
            ->assertOk()
            ->assertJsonPath('data.user_name', 'selfuser');
    }
}

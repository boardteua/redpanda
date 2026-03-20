<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BroadcastChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->configureReverbBroadcasterForTesting();
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    private function authChannel(?User $user, string $channelName): TestResponse
    {
        if ($user !== null) {
            return $this->actingAs($user, 'web')
                ->from(config('app.url'))
                ->withHeaders($this->statefulHeaders())
                ->post('/broadcasting/auth', [
                    'socket_id' => '1.1',
                    'channel_name' => $channelName,
                ]);
        }

        return $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->post('/broadcasting/auth', [
                'socket_id' => '1.1',
                'channel_name' => $channelName,
            ]);
    }

    public function test_unauthenticated_private_room_channel_is_denied(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $this->authChannel(null, 'private-room.'.$public->room_id)
            ->assertForbidden();
    }

    public function test_guest_can_subscribe_to_public_room_channel(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $guest = User::factory()->guest()->create();

        $this->authChannel($guest, 'private-room.'.$public->room_id)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_guest_cannot_subscribe_to_registered_only_room_channel(): void
    {
        $registered = Room::query()->create([
            'room_name' => 'VIP',
            'topic' => null,
            'access' => 1,
        ]);

        $guest = User::factory()->guest()->create();

        $this->authChannel($guest, 'private-room.'.$registered->room_id)
            ->assertForbidden();
    }

    public function test_registered_user_can_subscribe_to_registered_only_room_channel(): void
    {
        $registered = Room::query()->create([
            'room_name' => 'VIP',
            'topic' => null,
            'access' => 1,
        ]);

        $user = User::factory()->create();

        $this->authChannel($user, 'private-room.'.$registered->room_id)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_unknown_room_channel_is_denied(): void
    {
        $user = User::factory()->create();

        $this->authChannel($user, 'private-room.999999')
            ->assertForbidden();
    }

    public function test_user_can_subscribe_only_to_own_user_channel(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->authChannel($alice, 'private-user.'.$bob->id)
            ->assertForbidden();

        $this->authChannel($alice, 'private-user.'.$alice->id)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatRbacApiTest extends TestCase
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

    public function test_registered_user_without_vip_does_not_see_vip_room_in_list(): void
    {
        Room::query()->create(['room_name' => 'Pub', 'topic' => null, 'access' => Room::ACCESS_PUBLIC]);
        Room::query()->create(['room_name' => 'VIP', 'topic' => null, 'access' => Room::ACCESS_VIP]);

        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_vip_user_sees_vip_room(): void
    {
        Room::query()->create(['room_name' => 'Pub', 'topic' => null, 'access' => Room::ACCESS_PUBLIC]);
        $vipRoom = Room::query()->create(['room_name' => 'VIP', 'topic' => null, 'access' => Room::ACCESS_VIP]);

        $vip = User::factory()->vip()->create();

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['room_id' => $vipRoom->room_id]);
    }

    public function test_plain_user_cannot_post_in_vip_room(): void
    {
        $vipRoom = Room::query()->create(['room_name' => 'VIP', 'topic' => null, 'access' => Room::ACCESS_VIP]);
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$vipRoom->room_id}/messages", [
                'message' => 'nope',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertForbidden();
    }

    public function test_vip_user_can_post_in_vip_room(): void
    {
        $vipRoom = Room::query()->create(['room_name' => 'VIP', 'topic' => null, 'access' => Room::ACCESS_VIP]);
        $vip = User::factory()->vip()->create();

        $this->from(config('app.url'))
            ->actingAs($vip, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$vipRoom->room_id}/messages", [
                'message' => 'vip hello',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.post_color', 'vip');
    }

    public function test_auth_user_includes_chat_role_and_badge(): void
    {
        $mod = User::factory()->moderator()->create();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.chat_role', 'moderator')
            ->assertJsonPath('data.badge_color', '#16a34a');
    }

    public function test_guest_cannot_attach_image_to_message(): void
    {
        $room = Room::query()->create(['room_name' => 'Pub', 'topic' => null, 'access' => Room::ACCESS_PUBLIC]);
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => '',
                'image_id' => 1,
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_id']);
    }
}

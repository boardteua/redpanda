<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\Chat\SystemBotMessageService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemBotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array{0: Room, 1: Room}
     */
    private function seedTwoPublicRooms(): array
    {
        $a = Room::query()->create([
            'room_name' => 'Hub',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $b = Room::query()->create([
            'room_name' => 'Side',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        return [$a, $b];
    }

    public function test_first_room_messages_load_sends_welcome_once(): void
    {
        [$hub] = $this->seedTwoPublicRooms();
        User::factory()->systemChatBot()->create();

        $user = User::factory()->create(['user_name' => 'sb_welcome_user']);

        Sanctum::actingAs($user);
        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->getJson('/api/v1/rooms/'.$hub->room_id.'/messages?limit=20')
            ->assertOk();

        $this->assertSame(1, ChatMessage::query()->where('type', 'system')->where('system_kind', 'room_welcome')->count());

        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->getJson('/api/v1/rooms/'.$hub->room_id.'/messages?limit=20')
            ->assertOk();

        $this->assertSame(1, ChatMessage::query()->where('type', 'system')->where('system_kind', 'room_welcome')->count());
    }

    public function test_second_visit_sends_join_debounced(): void
    {
        [$hub] = $this->seedTwoPublicRooms();
        User::factory()->systemChatBot()->create();

        $user = User::factory()->create(['user_name' => 'sb_join_user']);

        Sanctum::actingAs($user);
        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->getJson('/api/v1/rooms/'.$hub->room_id.'/messages?limit=20')
            ->assertOk();

        $this->assertSame(0, ChatMessage::query()->where('system_kind', 'room_join')->count());

        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->getJson('/api/v1/rooms/'.$hub->room_id.'/messages?limit=20')
            ->assertOk();

        $this->assertSame(1, ChatMessage::query()->where('system_kind', 'room_join')->count());

        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->getJson('/api/v1/rooms/'.$hub->room_id.'/messages?limit=20')
            ->assertOk();

        $this->assertSame(1, ChatMessage::query()->where('system_kind', 'room_join')->count());
    }

    public function test_new_public_room_posts_announcement_in_hub(): void
    {
        [$hub] = $this->seedTwoPublicRooms();
        config(['chat.bot_announce_room_id' => $hub->room_id]);

        User::factory()->systemChatBot()->create();

        $creator = User::factory()->vip()->create();

        Sanctum::actingAs($creator);
        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->postJson('/api/v1/rooms', [
                'room_name' => 'Fresh public',
                'topic' => null,
            ])
            ->assertCreated();

        $newId = Room::query()->where('room_name', 'Fresh public')->value('room_id');
        $this->assertNotNull($newId);

        $ann = ChatMessage::query()
            ->where('post_roomid', $hub->room_id)
            ->where('type', 'system')
            ->where('system_kind', 'new_public_room')
            ->first();

        $this->assertNotNull($ann);
        $this->assertSame((int) $newId, (int) $ann->system_target_room_id);
        $this->assertNotNull($ann->system_action_label);
    }

    public function test_system_bot_profile_patch_forbidden_for_non_admin(): void
    {
        User::factory()->systemChatBot()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->patchJson('/api/v1/chat/system-bot/profile', [
                'user_name' => 'Hacker',
            ])
            ->assertForbidden();
    }

    public function test_system_bot_profile_patch_ok_for_chat_admin(): void
    {
        User::factory()->systemChatBot()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);
        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->patchJson('/api/v1/chat/system-bot/profile', [
                'user_name' => 'RedPandaBot',
            ])
            ->assertOk()
            ->assertJsonPath('data.user_name', 'RedPandaBot');
    }

    public function test_staff_patch_profile_rejected_for_system_bot(): void
    {
        $bot = User::factory()->systemChatBot()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);
        $this->from(config('app.url'))
            ->withHeaders(['Referer' => config('app.url')])
            ->patchJson('/api/v1/mod/users/'.$bot->id.'/profile', [
                'profile' => ['about' => 'x'],
            ])
            ->assertStatus(422);
    }

    public function test_system_message_post_color_and_contract_fields(): void
    {
        [$hub, $side] = $this->seedTwoPublicRooms();
        User::factory()->systemChatBot()->create();

        $svc = app(SystemBotMessageService::class);
        $msg = $svc->postSystemMessage($hub, SystemBotMessageService::KIND_NEW_PUBLIC_ROOM, 't', (int) $side->room_id, 'Go');

        $this->assertNotNull($msg);
        $this->assertSame('system', $msg->post_color);
        $this->assertSame((int) $side->room_id, (int) $msg->system_target_room_id);
        $this->assertSame('Go', $msg->system_action_label);
    }
}

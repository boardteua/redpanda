<?php

namespace Tests\Feature\Ai;

use App\Jobs\PostRudaPandaRoomIcebreakerJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Chat\SystemBotMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RudaPandaRoomIcebreakerTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_setting_noops(): void
    {
        $room = Room::query()->create(['room_name' => 'Public', 'topic' => null, 'access' => 0]);
        User::factory()->create(['is_system_bot' => true, 'guest' => false]);

        ChatSetting::current()->update([
            'ai_icebreaker_enabled' => false,
        ]);

        $job = new PostRudaPandaRoomIcebreakerJob((int) $room->room_id, 'k1');
        $job->handle(app(SystemBotMessageService::class));

        $this->assertDatabaseCount('chat', 0);
    }

    public function test_posts_when_room_is_idle_and_enabled(): void
    {
        $room = Room::query()->create(['room_name' => 'Public', 'topic' => null, 'access' => 0]);
        $bot = User::factory()->create(['is_system_bot' => true, 'guest' => false]);
        $user = User::factory()->create(['is_system_bot' => false, 'guest' => false]);

        ChatSetting::current()->update([
            'ai_icebreaker_enabled' => true,
            'ai_icebreaker_idle_minutes' => 60,
            'ai_icebreaker_cooldown_minutes' => 180,
            'ai_icebreaker_jitter_minutes' => 0,
        ]);

        $ts = now()->subHours(2)->timestamp;
        ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => $ts,
            'post_time' => date('H:i', $ts),
            'post_user' => $user->user_name,
            'post_message' => 'hello',
            'post_style' => null,
            'post_color' => null,
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => 'c1',
            'moderation_flag_at' => null,
            'post_deleted_at' => null,
            'post_edited_at' => null,
        ]);

        RateLimiter::clear('ruda-panda:icebreaker:room:'.$room->room_id);

        $job = new PostRudaPandaRoomIcebreakerJob((int) $room->room_id, 'k2');
        $job->handle(app(SystemBotMessageService::class));

        $this->assertDatabaseCount('chat', 2);
        $last = ChatMessage::query()->orderByDesc('post_id')->firstOrFail();
        $this->assertSame((int) $bot->id, (int) $last->user_id);
        $this->assertSame('public', $last->type);
        $this->assertSame((int) $room->room_id, (int) $last->post_roomid);
        $this->assertSame(0, (int) $last->file);
        $this->assertNotSame('', trim((string) $last->post_message));
    }

    public function test_cooldown_prevents_spam(): void
    {
        $room = Room::query()->create(['room_name' => 'Public', 'topic' => null, 'access' => 0]);
        User::factory()->create(['is_system_bot' => true, 'guest' => false]);
        $user = User::factory()->create(['is_system_bot' => false, 'guest' => false]);

        ChatSetting::current()->update([
            'ai_icebreaker_enabled' => true,
            'ai_icebreaker_idle_minutes' => 60,
            'ai_icebreaker_cooldown_minutes' => 180,
            'ai_icebreaker_jitter_minutes' => 0,
        ]);

        $ts = now()->subHours(2)->timestamp;
        ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => $ts,
            'post_time' => date('H:i', $ts),
            'post_user' => $user->user_name,
            'post_message' => 'hello',
            'post_style' => null,
            'post_color' => null,
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => 'c1',
            'moderation_flag_at' => null,
            'post_deleted_at' => null,
            'post_edited_at' => null,
        ]);

        RateLimiter::clear('ruda-panda:icebreaker:room:'.$room->room_id);

        $job1 = new PostRudaPandaRoomIcebreakerJob((int) $room->room_id, 'k2');
        $job1->handle(app(SystemBotMessageService::class));

        $job2 = new PostRudaPandaRoomIcebreakerJob((int) $room->room_id, 'k3');
        $job2->handle(app(SystemBotMessageService::class));

        $this->assertDatabaseCount('chat', 2);
    }
}

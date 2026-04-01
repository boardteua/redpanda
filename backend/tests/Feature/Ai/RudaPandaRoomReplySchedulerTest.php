<?php

namespace Tests\Feature\Ai;

use App\Jobs\PostRudaPandaRoomReplyJob;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RudaPandaRoomReplySchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_dispatches_with_delay_and_throttles_per_room(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        ChatSetting::query()->firstOrFail()->update([
            'ai_llm_enabled' => true,
            'ai_bot_room_max_replies_per_window' => 1,
            'ai_bot_room_window_seconds' => 60,
            'ai_bot_global_max_replies_per_window' => 100,
            'ai_bot_global_window_seconds' => 60,
            'ai_bot_reply_delay_min_ms' => 1200,
            'ai_bot_reply_delay_max_ms' => 1200,
            'ai_bot_max_reply_chars' => 200,
        ]);

        RateLimiter::clear('ruda-panda:room:'.$room->room_id);
        RateLimiter::clear('ruda-panda:global');

        Queue::fake([PostRudaPandaRoomReplyJob::class]);

        $svc = $this->app->make(RudaPandaRoomReplyScheduler::class);

        $ok1 = $svc->schedule($room, "hello\nworld", 'k1');
        $this->assertTrue($ok1);

        Queue::assertPushed(PostRudaPandaRoomReplyJob::class, function (PostRudaPandaRoomReplyJob $job) use ($room) {
            return $job->roomId === (int) $room->room_id
                && $job->replyText === 'hello world'
                && $job->idempotencyKey === 'k1';
        });

        $ok2 = $svc->schedule($room, 'second', 'k2');
        $this->assertFalse($ok2);

        Queue::assertPushed(PostRudaPandaRoomReplyJob::class, 1);
    }

    public function test_scheduler_noops_when_llm_master_disabled(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        ChatSetting::query()->firstOrFail()->update([
            'ai_llm_enabled' => false,
            'ai_bot_room_max_replies_per_window' => 10,
            'ai_bot_room_window_seconds' => 60,
            'ai_bot_global_max_replies_per_window' => 100,
            'ai_bot_global_window_seconds' => 60,
            'ai_bot_reply_delay_min_ms' => 0,
            'ai_bot_reply_delay_max_ms' => 0,
            'ai_bot_max_reply_chars' => 200,
        ]);

        Queue::fake([PostRudaPandaRoomReplyJob::class]);

        $svc = $this->app->make(RudaPandaRoomReplyScheduler::class);
        $ok = $svc->schedule($room, 'hello', 'k-off');

        $this->assertFalse($ok);
        Queue::assertNothingPushed();
    }
}


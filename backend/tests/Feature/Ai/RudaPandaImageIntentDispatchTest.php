<?php

namespace Tests\Feature\Ai;

use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Jobs\GenerateRudaPandaVipImageJob;
use App\Jobs\PostRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RudaPandaRoomResponder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class RudaPandaImageIntentDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_vip_img_message_dispatches_image_job_and_not_text_job(): void
    {
        Bus::fake();

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
            'ai_bot_enabled' => true,
        ]);

        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);

        $vip = User::factory()->create(['guest' => false, 'vip' => true, 'user_name' => 'Alice']);

        $msg = ChatMessage::query()->create([
            'user_id' => $vip->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $vip->user_name,
            'post_message' => '/img руду панду на велосипеді',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $this->app->make(RudaPandaRoomResponder::class)->maybeDispatchForMessage($msg, $room);

        Bus::assertDispatched(GenerateRudaPandaVipImageJob::class);
        Bus::assertNotDispatched(GenerateRudaPandaRoomReplyJob::class);
    }

    public function test_non_vip_img_message_dispatches_denial_post_job(): void
    {
        Bus::fake();

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
            'ai_bot_enabled' => true,
        ]);

        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);

        $user = User::factory()->create(['guest' => false, 'user_name' => 'Bob']);
        // Ensure this is truly a regular user (vip/user_rank aren't fillable).
        $user->forceFill(['vip' => false, 'user_rank' => User::RANK_USER])->save();
        $this->assertFalse($user->fresh()->isVip());
        $this->assertFalse($user->fresh()->canModerate());

        $msg = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => '/img кота у стилі піксель-арт',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $this->app->make(RudaPandaRoomResponder::class)->maybeDispatchForMessage($msg, $room);

        Bus::assertDispatched(PostRudaPandaRoomReplyJob::class, function (PostRudaPandaRoomReplyJob $job) use ($room): bool {
            return (int) $job->roomId === (int) $room->room_id
                && (str_contains($job->replyText, 'VIP') || str_contains($job->replyText, 'ВІП'));
        });
        Bus::assertNotDispatched(GenerateRudaPandaVipImageJob::class);
    }
}


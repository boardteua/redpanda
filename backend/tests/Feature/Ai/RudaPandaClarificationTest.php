<?php

namespace Tests\Feature\Ai;

use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class RudaPandaClarificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_request_clarification_with_single_question_prefix(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
            'ai_bot_enabled' => true,
        ]);

        User::factory()->create(['is_system_bot' => true, 'user_name' => 'Ruda Panda']);

        $user = User::factory()->create(['guest' => false, 'vip' => false, 'user_name' => 'Alice']);

        ChatSetting::query()->firstOrFail()->update([
            'ai_llm_enabled' => true,
            'ai_bot_reply_delay_min_ms' => 0,
            'ai_bot_reply_delay_max_ms' => 0,
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Уточнення: про який саме період ти питаєш?'],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $trigger = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Руда панда, поясни це.',
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

        $job = new GenerateRudaPandaRoomReplyJob(
            roomId: (int) $room->room_id,
            triggerPostId: (int) $trigger->post_id,
            triggerUserId: (int) $user->id,
            triggerText: (string) $trigger->post_message,
            idempotencyKey: (string) Str::uuid(),
        );

        $job->handle(
            $this->app->make(\App\Services\Ai\Gemini\GeminiClient::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler::class),
        );

        $this->assertDatabaseHas('chat', [
            'post_roomid' => $room->room_id,
            'post_message' => 'Уточнення: про який саме період ти питаєш?',
        ]);
    }

    public function test_after_clarification_user_reply_is_included_in_next_payload_context(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
            'ai_bot_enabled' => true,
        ]);

        User::factory()->create(['is_system_bot' => true, 'user_name' => 'Ruda Panda']);

        $user = User::factory()->create(['guest' => false, 'vip' => false, 'user_name' => 'Alice']);

        ChatSetting::query()->firstOrFail()->update([
            'ai_llm_enabled' => true,
            'ai_bot_reply_delay_min_ms' => 0,
            'ai_bot_reply_delay_max_ms' => 0,
        ]);

        $seenPayloads = [];

        Http::fake(function ($request) use (&$seenPayloads) {
            $json = $request->data();
            $seenPayloads[] = $json;

            // Always return a non-clarifying short answer.
            return Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Добре, зрозуміло.'],
                        ],
                    ],
                ]],
            ], 200);
        });

        $trigger1 = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Руда панда, поясни це.',
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

        $job1 = new GenerateRudaPandaRoomReplyJob(
            roomId: (int) $room->room_id,
            triggerPostId: (int) $trigger1->post_id,
            triggerUserId: (int) $user->id,
            triggerText: (string) $trigger1->post_message,
            idempotencyKey: (string) Str::uuid(),
        );

        $job1->handle(
            $this->app->make(\App\Services\Ai\Gemini\GeminiClient::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler::class),
        );

        $trigger2 = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Маю на увазі останні 2 роки.',
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

        $job2 = new GenerateRudaPandaRoomReplyJob(
            roomId: (int) $room->room_id,
            triggerPostId: (int) $trigger2->post_id,
            triggerUserId: (int) $user->id,
            triggerText: (string) $trigger2->post_message,
            idempotencyKey: (string) Str::uuid(),
        );

        $job2->handle(
            $this->app->make(\App\Services\Ai\Gemini\GeminiClient::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler::class),
        );

        $this->assertNotEmpty($seenPayloads);
        $last = $seenPayloads[count($seenPayloads) - 1];
        $this->assertIsArray($last);

        $joined = json_encode($last, JSON_UNESCAPED_UNICODE);
        $this->assertIsString($joined);

        // Second trigger text should be present in the payload contents.
        $this->assertStringContainsString('Маю на увазі останні 2 роки.', $joined);
    }
}


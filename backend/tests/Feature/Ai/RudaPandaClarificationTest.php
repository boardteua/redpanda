<?php

namespace Tests\Feature\Ai;

use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RudaPandaRoomResponder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class RudaPandaClarificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_includes_retrieved_room_snippets_before_llm_call(): void
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

        // Seed some room history (non-bot public message).
        ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time() - 3600,
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Я вчора купив Shimano Deore і не знаю, як налаштувати перемикач.',
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

        $seenPayloads = [];
        Http::fake(function ($request) use (&$seenPayloads) {
            $json = $request->data();
            $seenPayloads[] = $json;

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

        $trigger = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Руда панда, поясни як налаштувати Shimano Deore.',
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
            $this->app->make(\App\Services\Ai\Gemini\GeminiResponseParser::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomRetrievalService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler::class),
        );

        $this->assertNotEmpty($seenPayloads);
        $last = $seenPayloads[count($seenPayloads) - 1];
        $joined = json_encode($last, JSON_UNESCAPED_UNICODE);
        $this->assertIsString($joined);

        $this->assertStringContainsString('Релевантні фрагменти з минулих повідомлень', $joined);
        $this->assertStringContainsString('Shimano Deore', $joined);
    }

    public function test_model_can_request_clarification_with_single_question_prefix(): void
    {
        Bus::fake();

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
            $this->app->make(\App\Services\Ai\Gemini\GeminiResponseParser::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomRetrievalService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaRoomReplyScheduler::class),
        );

        Bus::assertDispatched(\App\Jobs\PostRudaPandaRoomReplyJob::class, function (\App\Jobs\PostRudaPandaRoomReplyJob $job) use ($room): bool {
            return (int) $job->roomId === (int) $room->room_id
                && $job->replyText === 'Уточнення: про який саме період ти питаєш?';
        });
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
            $this->app->make(\App\Services\Ai\Gemini\GeminiResponseParser::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomRetrievalService::class),
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
            $this->app->make(\App\Services\Ai\Gemini\GeminiResponseParser::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RudaPandaModelRouter::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomMemoryService::class),
            $this->app->make(\App\Services\Ai\RudaPanda\RoomRetrievalService::class),
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

    public function test_after_bot_clarification_followup_without_mention_still_triggers_job_via_llm(): void
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

        // LLM classifier call inside responder.
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'YES'],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        // Emulate "bot asked clarification" state from previous job run (T182).
        $stateKey = 'ruda-panda:clarify:room:'.$room->room_id;
        cache()->put($stateKey, [
            'topic' => hash('sha1', 'x'),
            'count' => 1,
            'awaiting_user_id' => (int) $user->id,
            'awaiting_until' => time() + 10 * 60,
            'awaiting_remaining' => 2,
            'bot_question' => 'Перепрошую, про який саме період ти питаєш?',
        ], now()->addHours(6));

        $msg = ChatMessage::query()->create([
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

        Bus::fake();

        $this->app->make(RudaPandaRoomResponder::class)->maybeDispatchForMessage($msg, $room);

        Bus::assertDispatched(GenerateRudaPandaRoomReplyJob::class);
    }

    public function test_after_recent_bot_message_question_without_mention_can_trigger_via_llm(): void
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

        // Pretend bot posted recently (this is what PostRudaPandaRoomReplyJob stores).
        cache()->put('ruda-panda:last-bot:room:'.$room->room_id, [
            'post_id' => 123,
            'ts' => time(),
            'text' => 'Які саме деталі ти маєш на увазі?',
        ], now()->addHours(6));

        // LLM classifier call inside responder.
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'YES'],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $msg = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Тобто ти радив Shimano чи SRAM?',
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

        Bus::fake();

        $this->app->make(RudaPandaRoomResponder::class)->maybeDispatchForMessage($msg, $room);

        Bus::assertDispatched(GenerateRudaPandaRoomReplyJob::class);
    }
}


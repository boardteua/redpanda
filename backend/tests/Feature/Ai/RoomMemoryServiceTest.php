<?php

namespace Tests\Feature\Ai;

use App\Models\ChatAiRoomSummary;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RoomMemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomMemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rollup_summary_moves_pointer_and_limits_context_window(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        ChatSetting::query()->firstOrFail()->update([
            'ai_summary_window_hours' => 3,
            'ai_summary_rollup_chunk_size' => 20,
            'ai_summary_max_chars' => 8000,
        ]);

        $author = User::factory()->create(['user_name' => 'Alice']);

        $now = time();
        $old = $now - (4 * 3600);

        for ($i = 1; $i <= 55; $i++) {
            ChatMessage::query()->create([
                'user_id' => $author->id,
                'post_date' => $i <= 30 ? $old : $now,
                'post_time' => date('H:i', $i <= 30 ? $old : $now),
                'post_user' => $author->user_name,
                'post_message' => 'm'.$i,
                'post_style' => null,
                'post_color' => 'user',
                'post_roomid' => $room->room_id,
                'type' => 'public',
                'post_target' => null,
                'avatar' => null,
                'file' => 0,
                'client_message_id' => (string) Str::uuid(),
            ]);
        }

        $svc = $this->app->make(RoomMemoryService::class);
        $svc->rollupSummary($room);

        $summary = ChatAiRoomSummary::query()->where('room_id', $room->room_id)->first();
        $this->assertNotNull($summary);
        $this->assertGreaterThan(0, $summary->summary_until_post_id);
        $this->assertNotNull($summary->summary_text);
        $this->assertStringContainsString('Alice: m1', $summary->summary_text);

        $ctx = $svc->buildContext($room, 10);
        $this->assertCount(10, $ctx['messages']);
        $this->assertTrue(collect($ctx['messages'])->every(
            fn (array $row) => (int) $row['post_id'] > (int) $summary->summary_until_post_id,
        ));
    }
}


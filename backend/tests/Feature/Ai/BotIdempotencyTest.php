<?php

namespace Tests\Feature\Ai;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use App\Support\IdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_client_message_id_can_be_deterministic_and_unique_per_bot_user(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $bot = User::factory()->create([
            'user_name' => 'Ruda Panda',
            'is_system_bot' => true,
        ]);

        $clientId = IdempotencyKey::toClientMessageId('bot-reply', 'room:'.$room->room_id.':seed');

        ChatMessage::query()->create([
            'user_id' => $bot->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $bot->user_name,
            'post_message' => 'first',
            'post_style' => null,
            'post_color' => 'system',
            'post_roomid' => $room->room_id,
            'type' => 'system',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => $clientId,
        ]);

        $this->expectException(QueryException::class);
        ChatMessage::query()->create([
            'user_id' => $bot->id,
            'post_date' => time(),
            'post_time' => date('H:i'),
            'post_user' => $bot->user_name,
            'post_message' => 'duplicate blocked by uniq index',
            'post_style' => null,
            'post_color' => 'system',
            'post_roomid' => $room->room_id,
            'type' => 'system',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => $clientId,
        ]);
    }
}


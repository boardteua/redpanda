<?php

namespace App\Jobs;

use App\Events\MessagePosted;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Services\Chat\SystemBotMessageService;
use App\Support\IdempotencyKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PostRudaPandaRoomReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(
        public int $roomId,
        public string $replyText,
        public string $idempotencyKey,
    ) {}

    public function handle(SystemBotMessageService $botMessages): void
    {
        $room = Room::query()->whereKey($this->roomId)->first();
        if ($room === null) {
            return;
        }

        $bot = $botMessages->botUser();
        if ($bot === null) {
            return;
        }

        $now = time();
        $clientId = IdempotencyKey::toClientMessageId('ruda-panda-reply', $this->idempotencyKey);

        try {
            $message = ChatMessage::query()->create([
                'user_id' => $bot->id,
                'post_date' => $now,
                'post_time' => date('H:i', $now),
                'post_user' => $bot->user_name,
                'post_message' => $this->replyText,
                'post_style' => null,
                'post_color' => 'system',
                'post_roomid' => $room->room_id,
                'type' => 'public',
                'post_target' => null,
                'avatar' => $bot->resolveAvatarUrl(),
                'file' => 0,
                'client_message_id' => $clientId,
                'moderation_flag_at' => null,
            ]);
        } catch (QueryException $e) {
            // Idempotency via unique index (user_id, client_message_id).
            if ($this->isDuplicateKey($e)) {
                return;
            }

            throw $e;
        }

        Log::channel('structured')->info('ruda-panda reply posted', [
            'room_id' => $room->room_id,
            'post_id' => $message->post_id,
        ]);

        broadcast(new MessagePosted($message))->toOthers();
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' && $driverCode === 1062;
    }
}


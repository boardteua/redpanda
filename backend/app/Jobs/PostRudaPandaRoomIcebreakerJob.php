<?php

namespace App\Jobs;

use App\Events\MessagePosted;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
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
use Illuminate\Support\Facades\RateLimiter;

/**
 * T183: idle-room icebreaker message.
 */
final class PostRudaPandaRoomIcebreakerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(
        public int $roomId,
        public string $idempotencyKey,
    ) {}

    public function handle(SystemBotMessageService $botMessages): void
    {
        $room = Room::query()->whereKey($this->roomId)->first();
        if ($room === null) {
            return;
        }

        $settings = ChatSetting::current();
        if (! $settings->ai_icebreaker_enabled) {
            return;
        }

        if (! ($room->ai_bot_enabled ?? true)) {
            return;
        }

        $idleMin = max(5, min(1440, (int) ($settings->ai_icebreaker_idle_minutes ?: 60)));
        $cooldownMin = max(5, min(10080, (int) ($settings->ai_icebreaker_cooldown_minutes ?: 180)));
        $jitterMin = max(0, min(120, (int) ($settings->ai_icebreaker_jitter_minutes ?: 10)));

        $now = now()->timestamp;
        $idleSeconds = $idleMin * 60;

        // Last non-bot public message timestamp.
        $lastUserTs = ChatMessage::query()
            ->join('users', 'users.id', '=', 'chat.user_id')
            ->where('chat.post_roomid', $room->room_id)
            ->whereNull('chat.post_deleted_at')
            ->where('chat.type', 'public')
            ->where('users.is_system_bot', false)
            ->max('chat.post_date');

        if (! is_int($lastUserTs) || $lastUserTs <= 0) {
            return;
        }

        $silence = $now - $lastUserTs;
        if ($silence < $idleSeconds) {
            return;
        }

        // Jitter: require exceeding the idle threshold by up to J minutes.
        if ($jitterMin > 0) {
            $extra = random_int(0, $jitterMin) * 60;
            if ($silence < $idleSeconds + $extra) {
                return;
            }
        }

        $cooldownKey = 'ruda-panda:icebreaker:room:'.$room->room_id;
        $ok = RateLimiter::attempt($cooldownKey, 1, static fn (): bool => true, $cooldownMin * 60);
        if (! $ok) {
            return;
        }

        $bot = $botMessages->botUser();
        if ($bot === null) {
            return;
        }

        $clientId = IdempotencyKey::toClientMessageId('ruda-panda-icebreaker', $this->idempotencyKey);

        $text = $this->pickTopic();

        try {
            $message = ChatMessage::query()->create([
                'user_id' => $bot->id,
                'post_date' => $now,
                'post_time' => date('H:i', $now),
                'post_user' => $bot->user_name,
                'post_message' => $text,
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
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            if ($sqlState === '23000' && $driverCode === 1062) {
                return;
            }

            throw $e;
        }

        Log::channel('structured')->info('ruda-panda icebreaker posted', [
            'room_id' => $room->room_id,
            'post_id' => $message->post_id,
            'idle_minutes' => (int) floor(($silence) / 60),
        ]);

        broadcast(new MessagePosted($message))->toOthers();
    }

    private function pickTopic(): string
    {
        $topics = [
            'Якби ти міг/могла миттєво навчитись одній навичці — що б це було?',
            'Яка дрібниця сьогодні зробила твій день трохи кращим?',
            'Фільм/серіал, який ти можеш радити без сорому?',
            'Якби треба було обрати одну страву на тиждень — що це?',
            'Топ-1 місце в місті, куди хочеться втекти на годинку?',
        ];

        return $topics[array_rand($topics)];
    }
}

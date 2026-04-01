<?php

namespace App\Jobs;

use App\Events\MessagePosted;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RudaPandaGeneratedImageStore;
use App\Services\Ai\RudaPanda\RudaPandaImageGenerator;
use App\Services\Ai\RudaPanda\RudaPandaIntent;
use App\Services\Ai\RudaPanda\RudaPandaModelRouter;
use App\Support\IdempotencyKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * T181: VIP-only image generation triggered from chat text.
 *
 * This job is intentionally independent of the message-ingestion pipeline;
 * it can be called by future "bot listener" tasks.
 */
final class GenerateRudaPandaVipImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 4;

    /**
     * @param  int  $triggerUserId  user who requested the image
     */
    public function __construct(
        public int $roomId,
        public int $triggerUserId,
        public string $prompt,
        public string $idempotencyKey,
    ) {}

    public function handle(
        RudaPandaModelRouter $router,
        RudaPandaImageGenerator $generator,
        RudaPandaGeneratedImageStore $store,
    ): void {
        $room = Room::query()->whereKey($this->roomId)->first();
        if ($room === null) {
            return;
        }

        $user = User::query()->whereKey($this->triggerUserId)->first();
        if ($user === null || $user->guest || ! $user->isVip()) {
            Log::channel('structured')->info('ruda-panda image denied (not vip)', [
                'room_id' => $room->room_id,
                'user_id' => $this->triggerUserId,
            ]);

            return;
        }

        $route = $router->routeIntent(RudaPandaIntent::Image, guest: false, vip: true);
        $result = $generator->generate($this->prompt, $route->modelId);
        if ($result === null) {
            Log::channel('structured')->warning('ruda-panda image generate returned no image', [
                'room_id' => $room->room_id,
                'user_id' => $user->id,
                'model' => $route->modelId,
            ]);

            return;
        }

        $bot = User::query()->where('is_system_bot', true)->orderBy('id')->first();
        if ($bot === null) {
            return;
        }

        $image = $store->storeForUser($bot, $result->binary, $result->mime);

        $clientId = IdempotencyKey::toClientMessageId('ruda-panda-image', $this->idempotencyKey);

        $text = trim((string) ($result->captionText ?? ''));
        if ($text === '') {
            $text = 'Ось зображення.';
        }

        try {
            $now = time();
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
                'file' => (int) $image->id,
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

        Log::channel('structured')->info('ruda-panda image posted', [
            'room_id' => $room->room_id,
            'post_id' => $message->post_id,
            'image_id' => $image->id,
            'model' => $route->modelId,
        ]);

        broadcast(new MessagePosted($message))->toOthers();
    }
}

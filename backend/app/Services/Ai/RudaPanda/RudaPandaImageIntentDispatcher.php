<?php

namespace App\Services\Ai\RudaPanda;

use App\Jobs\GenerateRudaPandaVipImageJob;
use App\Jobs\PostRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Str;

final class RudaPandaImageIntentDispatcher
{
    /**
     * @param  array<string, mixed>|null  $debugRow
     */
    public function dispatch(Room $room, ChatMessage $message, string $text, ?array &$debugRow): void
    {
        $user = User::query()->whereKey((int) $message->user_id)->first();
        $isAllowed = $user !== null && ! $user->guest && ($user->isVip() || $user->canModerate());
        $idempotencyKey = (string) ($message->client_message_id ?: Str::uuid());
        $prompt = $this->normalizeImagePrompt($text);

        if (! $isAllowed) {
            $deny = 'Я картинки тільки своїм малюю. Наний на ВІП і спробуй ще раз.';

            PostRudaPandaRoomReplyJob::dispatch(
                roomId: (int) $room->room_id,
                replyText: $deny,
                idempotencyKey: 'img-deny:'.$idempotencyKey,
            );

            if ($debugRow !== null) {
                $debugRow['dispatched'] = 'PostRudaPandaRoomReplyJob';
                $debugRow['note'] = 'image_denied_non_vip';
            }

            return;
        }

        GenerateRudaPandaVipImageJob::dispatch(
            roomId: (int) $room->room_id,
            triggerUserId: (int) $message->user_id,
            prompt: $prompt,
            idempotencyKey: 'img:'.$idempotencyKey,
        );

        if ($debugRow !== null) {
            $debugRow['dispatched'] = 'GenerateRudaPandaVipImageJob';
        }
    }

    private function normalizeImagePrompt(string $text): string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $text));
        $normalized = preg_replace('/^(руда\s+панда|панда)\b[,:!\.\s-]*/ui', '', $normalized) ?? $normalized;

        return trim($normalized);
    }
}

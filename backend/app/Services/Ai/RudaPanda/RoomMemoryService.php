<?php

namespace App\Services\Ai\RudaPanda;

use App\Models\ChatAiRoomSummary;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Support\Collection;

final class RoomMemoryService
{
    /**
     * Build context for the LLM: rolling summary + last N room messages.
     *
     * @return array{summary: string|null, messages: list<array{post_id:int,user:string,text:string}>}
     */
    public function buildContext(Room $room, int $maxMessages = 30): array
    {
        $maxMessages = max(1, min(200, $maxMessages));

        $summary = ChatAiRoomSummary::query()
            ->where('room_id', $room->room_id)
            ->first();

        $after = $summary?->summary_until_post_id ?? 0;

        $messages = ChatMessage::query()
            ->where('post_roomid', $room->room_id)
            ->whereNull('post_deleted_at')
            ->where('post_id', '>', $after)
            ->whereIn('type', ['public', 'system'])
            ->orderByDesc('post_id')
            ->limit($maxMessages)
            ->get(['post_id', 'post_user', 'post_message'])
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $m) => [
                'post_id' => (int) $m->post_id,
                'user' => (string) $m->post_user,
                'text' => (string) $m->post_message,
            ])
            ->all();

        return [
            'summary' => $summary?->summary_text,
            'messages' => $messages,
        ];
    }

    /**
     * Ensure we have a rolling summary so context window stays bounded.
     *
     * Strategy: when room history goes beyond the configured time window, we move
     * older messages into summary_text as a compact transcript, advancing the pointer.
     */
    public function rollupSummary(Room $room): ChatAiRoomSummary
    {
        $settings = ChatSetting::current();
        $windowHours = max(1, min(168, (int) ($settings->ai_summary_window_hours ?: 3)));
        $rollupChunkSize = max(5, min(200, (int) ($settings->ai_summary_rollup_chunk_size ?: 30)));
        $maxChars = max(200, min(32000, (int) ($settings->ai_summary_max_chars ?: 8000)));

        $summary = ChatAiRoomSummary::query()->firstOrCreate(
            ['room_id' => $room->room_id],
            ['summary_until_post_id' => 0, 'summary_text' => null],
        );

        $cutoffTs = time() - ($windowHours * 3600);

        // If there is nothing older than the window after the current pointer — nothing to do.
        $hasOld = ChatMessage::query()
            ->where('post_roomid', $room->room_id)
            ->whereNull('post_deleted_at')
            ->where('post_id', '>', $summary->summary_until_post_id)
            ->whereIn('type', ['public', 'system'])
            ->where('post_date', '<=', $cutoffTs)
            ->exists();

        if (! $hasOld) {
            return $summary;
        }

        /** @var Collection<int, ChatMessage> $chunk */
        $chunk = ChatMessage::query()
            ->where('post_roomid', $room->room_id)
            ->whereNull('post_deleted_at')
            ->where('post_id', '>', $summary->summary_until_post_id)
            ->whereIn('type', ['public', 'system'])
            ->where('post_date', '<=', $cutoffTs)
            ->orderBy('post_id')
            ->limit($rollupChunkSize)
            ->get(['post_id', 'post_user', 'post_message']);

        if ($chunk->isEmpty()) {
            return $summary;
        }

        $lines = $chunk->map(function (ChatMessage $m): string {
            $u = trim((string) $m->post_user);
            $t = trim((string) $m->post_message);
            return ($u === '' ? 'user' : $u).': '.preg_replace('/\s+/', ' ', $t);
        })->all();

        $append = implode("\n", $lines);
        $merged = trim((string) ($summary->summary_text ?? ''));
        $summary->summary_text = $merged === '' ? $append : ($merged."\n".$append);
        $summary->summary_until_post_id = (int) $chunk->last()->post_id;

        // Keep summary reasonably bounded even with long rooms.
        $summary->summary_text = $this->trimSummary($summary->summary_text, $maxChars);

        $summary->save();

        return $summary;
    }

    private function trimSummary(string $text, int $maxChars): string
    {
        $maxChars = max(200, $maxChars);
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, -$maxChars);
    }
}


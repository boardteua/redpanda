<?php

namespace App\Services\Ai\RudaPanda;

use App\Models\ChatMessage;
use App\Models\Room;

final class RoomRetrievalService
{
    /**
     * Lightweight "RAG" for T185 without embeddings/fulltext.
     *
     * We only look inside the same room, keep a hard cap on the scanned window,
     * and return short snippets ranked by lexical overlap.
     *
     * @return list<array{post_id:int,user:string,text:string,score:int}>
     */
    public function retrieveRelevantSnippets(Room $room, string $query, ?int $excludePostId = null, int $maxSnippets = 5): array
    {
        $maxSnippets = max(0, min(10, $maxSnippets));
        if ($maxSnippets === 0) {
            return [];
        }

        $tokens = $this->tokens($query);
        if ($tokens === []) {
            return [];
        }

        $windowDays = max(1, min(365, (int) config('chat.ai_retrieval_window_days', 30)));
        $cutoffTs = time() - ($windowDays * 24 * 3600); // bounded retention window (T185)
        $scanLimit = max(20, min(500, (int) config('chat.ai_retrieval_scan_limit', 200)));

        $q = ChatMessage::query()
            ->where('post_roomid', $room->room_id)
            ->whereNull('post_deleted_at')
            ->whereNull('moderation_flag_at')
            ->where('type', 'public')
            ->where('post_date', '>=', $cutoffTs);

        if ($excludePostId !== null && $excludePostId > 0) {
            $q->where('post_id', '!=', $excludePostId);
        }

        // Exclude system bots (Ruda Panda, etc.) from retrieval corpus.
        $q->whereHas('user', static fn ($u) => $u->where('is_system_bot', false));

        /** @var list<ChatMessage> $candidates */
        $candidates = $q->orderByDesc('post_id')
            ->limit($scanLimit)
            ->get(['post_id', 'post_user', 'post_message'])
            ->all();

        $scored = [];
        foreach ($candidates as $m) {
            $text = trim((string) $m->post_message);
            if ($text === '') {
                continue;
            }

            $score = $this->score($tokens, $text);
            if ($score <= 0) {
                continue;
            }

            $scored[] = [
                'post_id' => (int) $m->post_id,
                'user' => (string) $m->post_user,
                'text' => $this->snippet($text),
                'score' => $score,
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            $cmp = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ($b['post_id'] ?? 0) <=> ($a['post_id'] ?? 0);
        });

        return array_slice($scored, 0, $maxSnippets);
    }

    /**
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return [];
        }

        preg_match_all('/[\p{L}\p{N}_]{3,}/u', $text, $m);
        $raw = $m[0] ?? [];
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $stop = $this->stopwords();
        $out = [];
        foreach ($raw as $t) {
            $t = trim((string) $t);
            if ($t === '' || isset($stop[$t])) {
                continue;
            }
            $out[$t] = true;
        }

        return array_keys($out);
    }

    /**
     * @param  list<string>  $queryTokens
     */
    private function score(array $queryTokens, string $candidateText): int
    {
        $text = mb_strtolower($candidateText);

        $score = 0;
        foreach ($queryTokens as $t) {
            // Cheap containment check is stable across DB drivers (tests run on sqlite).
            if (str_contains($text, $t)) {
                $score += 1;
            }
        }

        // Small bonus if multiple tokens matched.
        if ($score >= 3) {
            $score += 1;
        }

        return $score;
    }

    private function snippet(string $text): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($t === '') {
            return '';
        }

        $max = 220;
        if (mb_strlen($t) <= $max) {
            return $t;
        }

        return rtrim(mb_substr($t, 0, $max - 1)).'…';
    }

    /**
     * @return array<string, true>
     */
    private function stopwords(): array
    {
        // Minimal stopword set to avoid returning everything for short prompts.
        $words = [
            // uk/ru
            'що', 'це', 'та', 'але', 'як', 'яка', 'який', 'які', 'вже', 'ще', 'мені', 'мене', 'ти', 'ви', 'ми', 'вони',
            'про', 'для', 'коли', 'тому', 'тоді', 'бо', 'аби', 'щоб', 'тут', 'там', 'ось', 'цей', 'ця', 'ці', 'того',
            'так', 'ні', 'будь', 'ласка', 'поясни', 'поясніть',
            'что', 'это', 'как', 'когда', 'почему', 'зачем', 'для', 'про', 'это',
            // en
            'the', 'and', 'but', 'for', 'with', 'this', 'that', 'what', 'when', 'why', 'how', 'you', 'your', 'from', 'into',
            'please', 'explain',
        ];

        $out = [];
        foreach ($words as $w) {
            $out[$w] = true;
        }
        return $out;
    }
}


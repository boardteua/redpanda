<?php

namespace App\Services\Ai\RudaPanda;

final class RudaPandaTriggerDetector
{
    /**
     * MVP trigger: explicit mention (see T176 spec).
     */
    public function shouldRespond(string $messageText): bool
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $messageText) ?? ''));
        if ($t === '') {
            return false;
        }

        if (str_starts_with($t, '/panda ')) {
            return true;
        }

        // Image shortcut (T187): allow "/img ..." to act as a trigger.
        if (str_starts_with($t, '/img')) {
            return true;
        }

        // Very lightweight "direct mention" heuristics.
        if (str_contains($t, '@panda') || str_contains($t, 'руда панда') || str_contains($t, 'панда')) {
            return true;
        }

        return false;
    }
}


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

        // Very lightweight "direct mention" heuristics.
        if (str_contains($t, '@panda') || str_contains($t, 'руда панда') || str_contains($t, 'панда')) {
            return true;
        }

        return false;
    }
}


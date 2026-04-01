<?php

namespace App\Services\Ai\RudaPanda;

final class RudaPandaTriggerDetector
{
    /**
     * Substrings (after lower-case + whitespace collapse) that count as addressing the bot.
     * Includes vocative (пандо), Latin layout, glued spelling, and a common typo (панад).
     *
     * @var list<string>
     */
    private const MENTION_MARKERS = [
        '@rudapanda',
        '@ruda_panda',
        '@panda',
        'руда панда',
        'руда пандо',
        'rudapanda',
        'ruda panda',
        'рудапанда',
        'панда',
        'пандо',
        'панад',
    ];

    /**
     * MVP trigger: explicit mention (see T176 spec).
     */
    public function shouldRespond(string $messageText): bool
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $messageText) ?? ''));
        if ($t === '') {
            return false;
        }

        foreach (self::MENTION_MARKERS as $marker) {
            if (str_contains($t, $marker)) {
                return true;
            }
        }

        return false;
    }
}

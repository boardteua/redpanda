<?php

namespace App\Services\Ai\RudaPanda;

final class RudaPandaReplyFormatter
{
    public function format(string $raw, int $maxChars): string
    {
        $maxChars = max(40, min(2000, $maxChars));

        $text = trim($raw);
        if ($text === '') {
            return '';
        }

        // Single paragraph: no forced line breaks.
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);

        // Avoid obvious markdown/list formatting.
        $text = preg_replace('/(^|\s)[*-]{1,2}\s+/', '$1', $text) ?? $text;
        $text = preg_replace('/\s{2,}/', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > $maxChars) {
            $text = rtrim(mb_substr($text, 0, $maxChars - 1)).'…';
        }

        return $text;
    }
}


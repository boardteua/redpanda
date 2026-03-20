<?php

namespace App\Services\Moderation;

use App\Models\FilterWord;
use Illuminate\Support\Facades\Cache;

final class ContentWordFilter
{
    private const CACHE_KEY = 'moderation.filter_words';

    public function filter(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        $words = Cache::remember(self::CACHE_KEY, 120, function () {
            return FilterWord::query()
                ->orderByRaw('LENGTH(word) DESC')
                ->pluck('word')
                ->all();
        });

        foreach ($words as $word) {
            if ($word === '' || $word === null) {
                continue;
            }
            $message = $this->replaceInsensitive($message, (string) $word);
        }

        return $message;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function replaceInsensitive(string $haystack, string $needle): string
    {
        $len = mb_strlen($needle);
        if ($len === 0) {
            return $haystack;
        }

        $mask = str_repeat('*', $len);
        $lowerNeedle = mb_strtolower($needle);
        $result = $haystack;
        $offset = 0;

        while (($pos = mb_stripos($result, $needle, $offset, 'UTF-8')) !== false) {
            $segment = mb_substr($result, $pos, $len, 'UTF-8');
            if (mb_strtolower($segment, 'UTF-8') !== $lowerNeedle) {
                $offset = $pos + 1;

                continue;
            }
            $result = mb_substr($result, 0, $pos, 'UTF-8')
                .$mask
                .mb_substr($result, $pos + $len, null, 'UTF-8');
            $offset = $pos + $len;
        }

        return $result;
    }
}

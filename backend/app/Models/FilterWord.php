<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FilterWord extends Model
{
    public const CACHE_KEY = 'moderation.filter_word_rules';

    public const MATCH_SUBSTRING = 'substring';

    public const MATCH_WHOLE_WORD = 'whole_word';

    public const ACTION_MASK = 'mask';

    public const ACTION_REJECT = 'reject';

    public const ACTION_FLAG = 'flag';

    public const ACTION_TEMP_MUTE = 'temp_mute';

    protected $fillable = [
        'word',
        'category',
        'match_mode',
        'action',
        'mute_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mute_minutes' => 'integer',
        ];
    }

    /**
     * @return Collection<int, self>
     */
    public static function cachedRules(): Collection
    {
        return Cache::remember(self::CACHE_KEY, 120, function () {
            return static::query()
                ->orderByRaw('LENGTH(word) DESC')
                ->get();
        });
    }

    public static function flushModerationCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saving(function (FilterWord $word): void {
            $word->word = mb_strtolower(trim($word->word));
            $cat = trim((string) ($word->category ?? ''));
            $word->category = $cat !== '' ? $cat : 'default';
        });

        static::saved(function (): void {
            static::flushModerationCache();
        });

        static::deleted(function (): void {
            static::flushModerationCache();
        });
    }
}

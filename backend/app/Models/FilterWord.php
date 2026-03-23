<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FilterWord extends Model
{
    /** v2: кеш лише масивів атрибутів (без серіалізації моделей — уникнення __PHP_Incomplete_Class у Redis). */
    public const CACHE_KEY = 'moderation.filter_word_rules_v2';

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
     * @return Collection<int, self> моделі лише в пам’яті (з атрибутів кешу), не з БД
     */
    public static function cachedRules(): Collection
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = Cache::remember(self::CACHE_KEY, 120, function (): array {
            return static::query()
                ->orderByRaw('LENGTH(word) DESC')
                ->get()
                // Поля кешу = контракт для ChatAutomoderationService: нові колонки правил — додати сюди й переглянути споживачів.
                ->map(fn (self $w) => $w->only([
                    'id',
                    'word',
                    'category',
                    'match_mode',
                    'action',
                    'mute_minutes',
                ]))
                ->values()
                ->all();
        });

        return collect($rows)->map(function (array $attrs): self {
            $m = new self;
            $m->forceFill($attrs);
            $m->syncOriginal();

            return $m;
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

<?php

namespace App\Services\Moderation;

use App\Models\FilterWord;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class ChatAutomoderationService
{
    public function __construct(
        private readonly ModerationService $moderation,
    ) {}

    /**
     * Маскування для приватних / інлайн-приватних каналів (лише правила з action=mask).
     */
    public function maskPrivateChannelText(string $text): string
    {
        return $this->applyMaskRules($text, FilterWord::cachedRules());
    }

    /**
     * @return array{ok: true, text: string, flag: bool}|array{ok: false, message: string}
     */
    public function applyToPublicMessage(string $text, User $author): array
    {
        if ($author->canModerate()) {
            return ['ok' => true, 'text' => $text, 'flag' => false];
        }

        $rules = FilterWord::cachedRules();
        $matches = [];
        foreach ($rules as $rule) {
            if ($this->matches($text, $rule)) {
                $matches[] = $rule;
            }
        }

        foreach ($matches as $rule) {
            if ($rule->action === FilterWord::ACTION_REJECT) {
                Log::warning('moderation.automod.reject', [
                    'user_id' => $author->id,
                    'reason' => 'stop_word_reject',
                    'rule_id' => $rule->id,
                ]);

                return [
                    'ok' => false,
                    'message' => 'Повідомлення не пройшло автоматичну модерацію.',
                ];
            }
        }

        $muteMinutes = 0;
        $muteRuleId = null;
        foreach ($matches as $rule) {
            if ($rule->action === FilterWord::ACTION_TEMP_MUTE) {
                $m = (int) ($rule->mute_minutes ?? 0);
                if ($m <= 0) {
                    $m = (int) config('chat.automod_default_mute_minutes', 30);
                }
                $m = min(max($m, 1), 525600);
                if ($m > $muteMinutes) {
                    $muteMinutes = $m;
                    $muteRuleId = $rule->id;
                }
            }
        }

        if ($muteMinutes > 0) {
            $this->moderation->muteUser($author, $muteMinutes);
            Log::warning('moderation.automod.temp_mute', [
                'user_id' => $author->id,
                'minutes' => $muteMinutes,
                'rule_id' => $muteRuleId,
            ]);

            return [
                'ok' => false,
                'message' => 'Повідомлення відхилено; обліковий запис тимчасово обмежено у надсиланні.',
            ];
        }

        $flag = false;
        foreach ($matches as $rule) {
            if ($rule->action === FilterWord::ACTION_FLAG) {
                $flag = true;
                break;
            }
        }

        $masked = $this->applyMaskRules($text, $rules);

        return ['ok' => true, 'text' => $masked, 'flag' => $flag];
    }

    private function matches(string $text, FilterWord $rule): bool
    {
        $needle = $rule->word;
        if ($needle === '') {
            return false;
        }
        if ($rule->match_mode === FilterWord::MATCH_WHOLE_WORD) {
            return $this->containsWholeWord($text, $needle);
        }

        return mb_stripos($text, $needle, 0, 'UTF-8') !== false;
    }

    private function containsWholeWord(string $haystack, string $needle): bool
    {
        $quoted = preg_quote($needle, '/');

        return preg_match(
            '/(?<![\p{L}\p{N}])'.$quoted.'(?![\p{L}\p{N}])/iu',
            $haystack
        ) === 1;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FilterWord>  $rules
     */
    private function applyMaskRules(string $text, $rules): string
    {
        $maskRules = $rules->filter(fn (FilterWord $r) => $r->action === FilterWord::ACTION_MASK)
            ->sortByDesc(fn (FilterWord $r) => mb_strlen($r->word));

        $result = $text;
        foreach ($maskRules as $rule) {
            if (! $this->matches($result, $rule)) {
                continue;
            }
            $result = $this->maskOccurrences($result, $rule);
        }

        return $result;
    }

    private function maskOccurrences(string $haystack, FilterWord $rule): string
    {
        $needle = $rule->word;
        $len = mb_strlen($needle);
        if ($len === 0) {
            return $haystack;
        }
        $mask = str_repeat('*', $len);
        if ($rule->match_mode === FilterWord::MATCH_WHOLE_WORD) {
            $quoted = preg_quote($needle, '/');

            return (string) preg_replace(
                '/(?<![\p{L}\p{N}])'.$quoted.'(?![\p{L}\p{N}])/iu',
                $mask,
                $haystack
            );
        }

        return $this->replaceSubstringInsensitive($haystack, $needle, $mask);
    }

    private function replaceSubstringInsensitive(string $haystack, string $needle, string $replacement): string
    {
        $len = mb_strlen($needle);
        if ($len === 0) {
            return $haystack;
        }

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
                .$replacement
                .mb_substr($result, $pos + $len, null, 'UTF-8');
            $offset = $pos + mb_strlen($replacement, 'UTF-8');
        }

        return $result;
    }
}

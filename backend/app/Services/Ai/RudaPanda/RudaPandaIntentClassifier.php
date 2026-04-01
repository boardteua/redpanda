<?php

namespace App\Services\Ai\RudaPanda;

/**
 * Евристичний класифікатор наміру для маршрутизації моделей (T180).
 * Легкий LLM-виклик для класифікації навмисне не додаємо — зайва латентність і вартість;
 * за потреби T184 може ввімкнути додатковий шар.
 */
final class RudaPandaIntentClassifier
{
    private const COMPLEX_MIN_CHARS = 480;

    /**
     * @var list<string>
     */
    private const IMAGE_PHRASES = [
        'згенеруй', 'згенерувати', 'намалюй', 'намалювати', 'картинк', 'зображен',
        'ілюстраці', 'фото з', 'generate image', 'draw me', 'draw a', 'image of',
        'picture of', 'make an image', '/img',
    ];

    /**
     * @var list<string>
     */
    private const COMPLEX_PHRASES = [
        'детально', 'розгорнуто', 'нарис', 'есе ', ' есе', 'аналіз', 'філософ',
        'порівняй', 'доведи', 'довести', 'алгоритм', 'чому саме', 'поясни крок',
        'explain in detail', 'step by step', 'prove that', 'formal proof',
        'essay', 'in depth', 'comprehensive', 'implement a', 'write a program',
        'refactor', 'big o', 'асимптот', 'архітектур', 'security audit',
    ];

    public function classify(string $text): RudaPandaIntent
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? ''));
        if ($t === '') {
            return RudaPandaIntent::Simple;
        }

        if ($this->matchesAny($t, self::IMAGE_PHRASES)) {
            return RudaPandaIntent::Image;
        }

        if (mb_strlen($t) >= self::COMPLEX_MIN_CHARS) {
            return RudaPandaIntent::Complex;
        }

        if ($this->matchesAny($t, self::COMPLEX_PHRASES)) {
            return RudaPandaIntent::Complex;
        }

        return RudaPandaIntent::Simple;
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if ($n !== '' && str_contains($haystack, $n)) {
                return true;
            }
        }

        return false;
    }
}

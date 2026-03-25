<?php

namespace App\Support;

/**
 * Прибирає типове «сміття» HTML у legacy-повідомленнях (порожні color/background у span,
 * зламані fancybox+img без href/src).
 */
final class LegacyChatHtmlGarbageCleaner
{
    private const MAX_PASSES = 24;

    public function clean(string $html): string
    {
        $out = $html;
        for ($i = 0; $i < self::MAX_PASSES; $i++) {
            $next = $this->passStripJunkSpans($out);
            if ($next === $out) {
                break;
            }
            $out = $next;
        }

        return $out;
    }

    public function wouldChange(string $html): bool
    {
        return $this->clean($html) !== $html;
    }

    private function passStripJunkSpans(string $html): string
    {
        return (string) preg_replace_callback(
            '/<span\b[^>]*\bstyle\s*=\s*(?:"([^"]*)"|\'([^\']*)\')[^>]*>([\s\S]*?)<\/span>/iu',
            function (array $m): string {
                $style = ($m[1] ?? '') !== '' ? $m[1] : ($m[2] ?? '');
                $inner = $m[3] ?? '';
                if (! $this->isJunkColorBackgroundOnlyStyle($style)) {
                    return $m[0];
                }
                if ($this->isRemovableOrEmptyInner($inner)) {
                    return '';
                }

                return $inner;
            },
            $html
        );
    }

    /**
     * Стиль виду « color:; background:; » (лише порожні color та background, без інших властивостей).
     */
    private function isJunkColorBackgroundOnlyStyle(string $style): bool
    {
        $t = strtolower(preg_replace('/\s+/', ' ', trim($style)));
        if ($t === '') {
            return false;
        }
        if (! preg_match('/\bcolor\s*:\s*;/', $t)) {
            return false;
        }
        if (! preg_match('/\bbackground\s*:\s*;/', $t)) {
            return false;
        }
        $rest = preg_replace('/\bcolor\s*:\s*;/i', '', $t);
        $rest = preg_replace('/\bbackground\s*:\s*;/i', '', $rest);
        $rest = trim(preg_replace('/[\s;]+/', ' ', $rest));

        return $rest === '';
    }

    private function isRemovableOrEmptyInner(string $inner): bool
    {
        if (trim($inner) === '') {
            return true;
        }

        return $this->isBrokenFancyboxEmptyMediaOnly($inner);
    }

    /**
     * <a class="…fancybox…" href="[лише пробіли/переноси]"><img src="[те саме]"/></a> з опційним пробілом навколо.
     */
    private function isBrokenFancyboxEmptyMediaOnly(string $inner): bool
    {
        return (bool) preg_match(
            '~\A\s*'.
            '<a\b(?=[^>]*\bclass\s*=\s*["\'][^"\']*fancybox)(?=[^>]*\bhref\s*=\s*["\'][\s\n\r]*["\'])[^>]*>\s*'.
            '<img\b[^>]*\bsrc\s*=\s*["\'][\s\n\r]*["\'][^>]*/?>\s*'.
            '</a>\s*\z~ix',
            $inner
        );
    }
}

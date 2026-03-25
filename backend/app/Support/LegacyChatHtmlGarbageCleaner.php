<?php

namespace App\Support;

/**
 * Прибирає типове «сміття» HTML у legacy-повідомленнях (порожні color/background у span,
 * зламані fancybox+img без href/src), знімає обгортку fancybox, коли href і src однакові,
 * замінює теги img з атрибутом src на голий URL (екранований для HTML).
 */
final class LegacyChatHtmlGarbageCleaner
{
    private const MAX_PASSES = 24;

    public function clean(string $html): string
    {
        $out = $html;
        for ($i = 0; $i < self::MAX_PASSES; $i++) {
            $next = $this->passStripJunkSpans($out);
            $next = $this->passUnwrapFancyboxSameUrlImage($next);
            $next = $this->passImgTagsToBareSrcUrl($next);
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

    /**
     * <a class="…fancybox…" href="URL"><img … src="URL" …></a> → лише тег img (типово після імпорту з board.te.ua).
     */
    private function passUnwrapFancyboxSameUrlImage(string $html): string
    {
        return (string) preg_replace_callback(
            '~<a\b([^>]+)>(\s*)<img\b([^>]+)>(\s*)</a>~iu',
            function (array $m): string {
                $aAttrs = $m[1];
                $imgWsBefore = $m[2];
                $imgAttrs = $m[3];
                $imgWsAfter = $m[4];
                if (! preg_match('/\bclass\s*=\s*["\'][^"\']*fancybox/i', $aAttrs)) {
                    return $m[0];
                }
                $href = $this->attributeFromAttrString($aAttrs, 'href');
                $src = $this->attributeFromAttrString($imgAttrs, 'src');
                if ($href === null || $src === null) {
                    return $m[0];
                }
                if (! $this->sameUrl($href, $src)) {
                    return $m[0];
                }

                $imgInner = ltrim($imgAttrs, " \t\n\r");

                return $imgWsBefore.'<img '.$imgInner.'>'.$imgWsAfter;
            },
            $html
        );
    }

    /**
     * &lt;img src="URL" …&gt; → текст URL (htmlspecialchars), без тега img.
     */
    private function passImgTagsToBareSrcUrl(string $html): string
    {
        return (string) preg_replace_callback(
            '~<img\b([^>]*)/?>~iu',
            function (array $m): string {
                $src = $this->attributeFromAttrString($m[1], 'src');
                if ($src === null || $src === '') {
                    return '';
                }

                return htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            },
            $html
        );
    }

    private function attributeFromAttrString(string $attrs, string $name): ?string
    {
        $qName = preg_quote($name, '~');
        if (preg_match('~\b'.$qName.'\s*=\s*"([^"]*)"~iu', $attrs, $m)) {
            return $this->decodeAttrValue($m[1]);
        }
        if (preg_match('~\b'.$qName."\s*=\s*'([^']*)'~iu", $attrs, $m)) {
            return $this->decodeAttrValue($m[1]);
        }

        return null;
    }

    private function decodeAttrValue(string $raw): string
    {
        return trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function sameUrl(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === $b) {
            return true;
        }

        return strcasecmp($a, $b) === 0;
    }
}

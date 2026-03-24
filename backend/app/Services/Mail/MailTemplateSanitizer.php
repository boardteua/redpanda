<?php

namespace App\Services\Mail;

/**
 * Обмежений HTML для листів з адмінки: без script/iframe, без inline event handlers, без javascript: у href.
 */
final class MailTemplateSanitizer
{
    private const ALLOWED_TAGS = '<p><br><br/><strong><b><em><i><u><a><h1><h2><h3><ul><ol><li><div><span><hr><table><thead><tbody><tr><th><td>';

    public static function html(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button|meta|link|base)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button|meta|link|base)[^>]*/>#is', '', $html) ?? '';
        $html = preg_replace('/\s?on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace_callback(
            '#<a\s+([^>]*?)>#i',
            function (array $m): string {
                $inner = $m[1];
                if (preg_match('/href\s*=\s*("|\')(.*?)\1/i', $inner, $hm)) {
                    $href = trim($hm[2]);
                    if ($href === '' || preg_match('#^https?://#i', $href) === 1 || (str_starts_with($href, 'mailto:') && strlen($href) < 500)) {
                        return '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8').'">';
                    }
                }

                return '<a>';
            },
            $html
        ) ?? $html;

        return trim($html);
    }
}

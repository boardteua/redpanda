<?php

namespace App\Services\Mail;

/**
 * Підстановка плейсхолдерів виду {{ key }}; невідомі ключі залишаються як є.
 *
 * @param  array<string, string>  $values
 */
final class MailPlaceholderRenderer
{
    public static function render(string $template, array $values): string
    {
        $out = preg_replace_callback(
            '/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/',
            function (array $m) use ($values): string {
                $k = $m[1];

                return array_key_exists($k, $values) ? $values[$k] : $m[0];
            },
            $template
        );

        return is_string($out) ? $out : $template;
    }
}

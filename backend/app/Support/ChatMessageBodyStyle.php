<?php

namespace App\Support;

/**
 * Дозволені пресети форматування тіла повідомлення (T30): без довільного HTML/CSS від клієнта.
 *
 * @phpstan-type StyleShape array{bold: bool, italic: bool, underline: bool, bg: string|null, fg: string|null}
 */
final class ChatMessageBodyStyle
{
    /** Ключі фону — взаємовиключні з {@see self::FG_KEYS} на рівні валідації. */
    public const BG_KEYS = ['amber', 'mint', 'sky', 'lavender', 'rose', 'sand'];

    /** Ключі кольору тексту (без фону). */
    public const FG_KEYS = ['blue', 'emerald', 'rose', 'violet', 'amber', 'slate'];

    /**
     * @param  array<string, mixed>|null  $validatedStyle  Поле `style` після FormRequest
     * @return StyleShape|null null — немає збереженого стилю
     */
    public static function fromValidated(?array $validatedStyle): ?array
    {
        if ($validatedStyle === null) {
            return null;
        }

        $bold = (bool) ($validatedStyle['bold'] ?? false);
        $italic = (bool) ($validatedStyle['italic'] ?? false);
        $underline = (bool) ($validatedStyle['underline'] ?? false);
        $bg = $validatedStyle['bg'] ?? null;
        $fg = $validatedStyle['fg'] ?? null;

        $bg = is_string($bg) && $bg !== '' ? $bg : null;
        $fg = is_string($fg) && $fg !== '' ? $fg : null;

        if (! $bold && ! $italic && ! $underline && $bg === null && $fg === null) {
            return null;
        }

        return [
            'bold' => $bold,
            'italic' => $italic,
            'underline' => $underline,
            'bg' => $bg,
            'fg' => $fg,
        ];
    }
}

<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Перевірка фактичного вмісту multipart-зображення (T19): MIME за вмістом, декодування, розміри.
 */
final class ChatUploadedImageInspector
{
    /** Максимальний розмір сторони (px) для аватара профілю. */
    public const AVATAR_MAX_DIMENSION = 4096;

    /** Максимальний розмір сторони (px) для зображень у чаті (вкладення). */
    public const CHAT_IMAGE_MAX_DIMENSION = 8192;

    /** @var array<string, string> */
    private const ALLOWED_CONTENT_TYPES = [
        'image/jpeg' => 'image/jpeg',
        'image/png' => 'image/png',
        'image/gif' => 'image/gif',
        'image/webp' => 'image/webp',
    ];

    /**
     * @return array{mime: string, width: positive-int, height: positive-int}
     */
    public static function inspectOrFail(UploadedFile $file, int $maxWidth, int $maxHeight): array
    {
        $path = $file->getRealPath();
        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'image' => 'Файл зображення недоступний для перевірки.',
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($path);
        if ($detected === false) {
            throw ValidationException::withMessages([
                'image' => 'Не вдалося визначити тип файлу за вмістом.',
            ]);
        }

        if (! isset(self::ALLOWED_CONTENT_TYPES[$detected])) {
            throw ValidationException::withMessages([
                'image' => 'Файл не є дозволеним зображенням (JPEG, PNG, GIF, WebP) за фактичним вмістом.',
            ]);
        }

        $canonicalMime = self::ALLOWED_CONTENT_TYPES[$detected];

        $dims = @getimagesize($path);
        if ($dims === false) {
            throw ValidationException::withMessages([
                'image' => 'Файл не є коректним зображенням або пошкоджений.',
            ]);
        }

        $width = (int) $dims[0];
        $height = (int) $dims[1];
        if ($width < 1 || $height < 1) {
            throw ValidationException::withMessages([
                'image' => 'Некоректний розмір зображення.',
            ]);
        }

        if ($width > $maxWidth || $height > $maxHeight) {
            throw ValidationException::withMessages([
                'image' => sprintf(
                    'Зображення завелике: максимум %d×%d пікселів (у вас %d×%d).',
                    $maxWidth,
                    $maxHeight,
                    $width,
                    $height
                ),
            ]);
        }

        $type = (int) ($dims[2] ?? 0);
        $typeMimeMap = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_GIF => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
        ];

        if (! isset($typeMimeMap[$type]) || $typeMimeMap[$type] !== $canonicalMime) {
            throw ValidationException::withMessages([
                'image' => 'Тип зображення не збігається з реальним вмістом файлу.',
            ]);
        }

        return [
            'mime' => $canonicalMime,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Безпечна назва для збереження в БД (без шляхів і керуючих символів).
     */
    public static function sanitizeDisplayBasename(string $originalFilenameBase, string $fallback = 'image'): string
    {
        $base = basename(str_replace('\\', '/', $originalFilenameBase));
        $s = preg_replace('/[^\p{L}\p{N}\s._-]+/u', '', $base);
        $s = trim((string) $s);

        if ($s === '') {
            return $fallback;
        }

        return mb_substr($s, 0, 200);
    }
}

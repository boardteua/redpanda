<?php

namespace App\Support;

use App\Models\ChatSetting;
use Illuminate\Http\Request;

/**
 * Спільна валідація multipart-поля зображення для чату та аватара (T86).
 */
final class ChatImageUploadValidation
{
    public static function validateUploadedImage(Request $request, string $attribute = 'image'): void
    {
        $effectiveBytes = ChatSetting::current()->effectiveMaxChatImageUploadBytes();
        $maxKb = max(1, (int) ceil($effectiveBytes / 1024));

        $request->validate([
            $attribute => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimetypes:image/jpeg,image/png,image/gif,image/webp',
            ],
        ], [
            $attribute.'.max' => sprintf(
                'Файл завеликий. Максимум %d байт (налаштування чату та обмеження PHP upload_max_filesize).',
                $effectiveBytes
            ),
        ]);
    }
}

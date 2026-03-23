<?php

namespace App\Services\Chat;

use App\Models\ChatEmoticon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Збереження файлів смайлів у public/emoticon (T63).
 */
class ChatEmoticonFileService
{
    private const ALLOWED_EXT = ['gif', 'png', 'webp'];

    public function publicEmoticonDirectory(): string
    {
        return public_path('emoticon');
    }

    /**
     * @return array{0: string, 1: string} [stored_basename, absolute_path]
     */
    public function storeUploaded(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if ($ext === '') {
            $guess = $file->guessExtension();
            $ext = $guess !== null ? strtolower($guess) : '';
        }
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Дозволені лише файли GIF, PNG або WebP.');
        }

        $basename = Str::lower(Str::ulid()).'.'.$ext;
        if (! $this->isSafeBasename($basename)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Некоректне ім’я файлу.');
        }

        $dir = $this->publicEmoticonDirectory();
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.$basename;
        $file->move($dir, $basename);

        return [$basename, $path];
    }

    public function isSafeBasename(string $name): bool
    {
        if ($name === '' || str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
    }

    public function deleteFileIfUnused(string $basename, int $exceptId): void
    {
        if (! $this->isSafeBasename($basename)) {
            return;
        }

        $stillUsed = ChatEmoticon::query()
            ->where('id', '!=', $exceptId)
            ->where('file_name', $basename)
            ->exists();

        if ($stillUsed) {
            return;
        }

        $path = $this->publicEmoticonDirectory().DIRECTORY_SEPARATOR.$basename;
        if (File::isFile($path)) {
            File::delete($path);
        }
    }
}

<?php

namespace App\Services\LegacyBoardImport;

use App\Models\Image;
use App\Models\User;
use App\Support\ChatUploadedImageInspector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Після rsync legacy-аватарок у каталог (T113): створення рядків images + users.avatar_image_id за збігом імені файлу з user_name.
 */
final class LegacyUserAvatarLinkService
{
    private const ALLOWED_EXT = ['gif', 'png', 'jpg', 'jpeg', 'webp'];

    /**
     * @return array{
     *     linked: int,
     *     skipped_no_file: int,
     *     skipped_invalid_image: int,
     *     skipped_error: int,
     * }
     */
    public function run(string $legacyDir, bool $dryRun, int $chunk): array
    {
        $legacyDir = rtrim($legacyDir, '/\\');
        if ($legacyDir === '' || ! is_dir($legacyDir)) {
            throw new \InvalidArgumentException('Каталог legacy-аватарок не існує або порожній шлях: '.$legacyDir);
        }

        $index = $this->buildStemIndex($legacyDir);
        $report = [
            'linked' => 0,
            'skipped_no_file' => 0,
            'skipped_invalid_image' => 0,
            'skipped_error' => 0,
        ];

        User::query()
            ->where('guest', false)
            ->whereNull('avatar_image_id')
            ->whereNotNull('legacy_imported_at')
            ->orderBy('id')
            ->chunkById(max(1, $chunk), function ($users) use (&$report, $index, $dryRun): void {
                foreach ($users as $user) {
                    /** @var User $user */
                    $key = mb_strtolower((string) $user->user_name, 'UTF-8');
                    if ($key === '' || ! isset($index[$key])) {
                        $report['skipped_no_file']++;

                        continue;
                    }

                    $srcPath = $index[$key];
                    $inspected = ChatUploadedImageInspector::tryInspectPath(
                        $srcPath,
                        ChatUploadedImageInspector::AVATAR_MAX_DIMENSION,
                        ChatUploadedImageInspector::AVATAR_MAX_DIMENSION
                    );
                    if ($inspected === null) {
                        $report['skipped_invalid_image']++;

                        continue;
                    }

                    if ($dryRun) {
                        $report['linked']++;

                        continue;
                    }

                    try {
                        $this->linkUserToFile($user, $srcPath, $inspected);
                        $report['linked']++;
                    } catch (Throwable) {
                        $report['skipped_error']++;
                    }
                }
            });

        return $report;
    }

    /**
     * @return array<string, string> mb_strtolower(stem) => absolute path
     */
    private function buildStemIndex(string $legacyDir): array
    {
        $index = [];
        foreach (File::files($legacyDir) as $fileInfo) {
            $this->addFileToStemIndex($index, $fileInfo->getPathname());
        }

        return $index;
    }

    /** @param array<string, string> $index */
    private function addFileToStemIndex(array &$index, string $absolutePath): void
    {
        $ext = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            return;
        }
        $stem = (string) pathinfo($absolutePath, PATHINFO_FILENAME);
        $key = mb_strtolower($stem, 'UTF-8');
        if ($key === '') {
            return;
        }
        if (! isset($index[$key])) {
            $index[$key] = $absolutePath;
        }
    }

    /**
     * @param  array{mime: string, width: int, height: int}  $inspected
     */
    private function linkUserToFile(User $user, string $srcPath, array $inspected): void
    {
        $ext = strtolower((string) pathinfo($srcPath, PATHINFO_EXTENSION));
        $displayBase = ChatUploadedImageInspector::sanitizeDisplayBasename((string) $user->user_name, 'legacy-avatar');
        $diskName = 'legacy-'.$displayBase.'.'.$ext;
        $diskName = str_replace(['/', '\\'], '', $diskName);
        if ($diskName === '.'.$ext || str_starts_with($diskName, 'legacy-.') || strlen($diskName) > 220) {
            $diskName = 'legacy-'.$user->id.'.'.$ext;
        }

        $relativePath = $user->id.'/avatars/'.$diskName;
        $bytes = file_get_contents($srcPath);
        if ($bytes === false) {
            throw new \RuntimeException('read_failed');
        }

        DB::transaction(function () use ($user, $relativePath, $bytes, $inspected, $displayBase): void {
            $disk = Storage::disk('chat_images');
            if ($disk->exists($relativePath)) {
                $disk->delete($relativePath);
            }
            if (! $disk->put($relativePath, $bytes)) {
                throw new \RuntimeException('put_failed');
            }

            $image = Image::query()->create([
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'disk_path' => $relativePath,
                'file_name' => $displayBase,
                'mime' => $inspected['mime'],
                'size_bytes' => strlen($bytes),
                'date_sent' => time(),
            ]);

            User::query()->whereKey($user->id)->update(['avatar_image_id' => $image->id]);
        });
    }
}

<?php

namespace App\Services\Ai\RudaPanda;

use App\Models\Image;
use App\Models\User;
use App\Support\ChatUploadedImageInspector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class RudaPandaGeneratedImageStore
{
    /**
     * Persist binary image into the same storage as chat uploads (T10) and return Image row.
     */
    public function storeForUser(User $owner, string $binary, string $preferredMime): Image
    {
        $disk = Storage::disk('chat_images');
        $dir = $owner->id.'/generated';
        $name = 'ruda-panda-'.Str::lower(Str::random(12));

        $ext = match ($preferredMime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'png',
        };

        $path = $dir.'/'.$name.'.'.$ext;

        if (! $disk->put($path, $binary)) {
            Log::warning('ruda-panda image store failed', [
                'user_id' => $owner->id,
                'disk_path' => $path,
            ]);
            throw new \RuntimeException('Failed to write generated image');
        }

        $fullPath = $disk->path($path);
        $inspected = ChatUploadedImageInspector::tryInspectPath(
            $fullPath,
            ChatUploadedImageInspector::CHAT_IMAGE_MAX_DIMENSION,
            ChatUploadedImageInspector::CHAT_IMAGE_MAX_DIMENSION,
        );

        if ($inspected === null) {
            $disk->delete($path);
            throw new \RuntimeException('Generated image failed inspection');
        }

        $now = time();

        return Image::query()->create([
            'user_id' => (int) $owner->id,
            'user_name' => (string) $owner->user_name,
            'disk_path' => $path,
            'file_name' => 'ruda-panda',
            'mime' => $inspected['mime'],
            'size_bytes' => (int) filesize($fullPath),
            'date_sent' => $now,
        ]);
    }
}

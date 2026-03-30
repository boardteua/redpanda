<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Публічне віддавання аватара за тимчасово підписаним URL (Web Push, без cookies).
 */
class PublicUserAvatarController extends Controller
{
    public function show(User $user): StreamedResponse|JsonResponse
    {
        $imageId = $user->avatar_image_id;
        if ($imageId === null) {
            return response()->json(['message' => 'Аватар не завантажено.'], 404);
        }

        $image = Image::query()->find($imageId);
        if ($image === null) {
            return response()->json(['message' => 'Файл не знайдено.'], 404);
        }

        $disk = Storage::disk('chat_images');
        if (! $disk->exists($image->disk_path)) {
            return response()->json(['message' => 'Файл не знайдено.'], 404);
        }

        return $disk->response($image->disk_path, $image->file_name, [
            'Content-Type' => $image->mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

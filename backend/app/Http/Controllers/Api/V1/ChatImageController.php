<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Services\Moderation\UserPostingGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatImageController extends Controller
{
    public function store(Request $request, UserPostingGate $postingGate): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'max:4096',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp',
            ],
        ]);

        $user = $request->user();
        $postingGate->ensureCanPost($user);
        $file = $request->file('image');
        $now = time();
        $path = $file->store((string) $user->id, 'chat_images');
        if ($path === false) {
            return response()->json(['message' => 'Не вдалося зберегти файл.'], 500);
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $displayName = $original !== '' ? mb_substr($original, 0, 200) : 'image';

        $image = Image::query()->create([
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'disk_path' => $path,
            'file_name' => $displayName,
            'mime' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            'size_bytes' => (int) $file->getSize(),
            'date_sent' => $now,
        ]);

        return response()->json([
            'data' => [
                'id' => $image->id,
                'url' => route('api.v1.chat-images.file', ['image' => $image->id], true),
                'mime' => $image->mime,
                'size_bytes' => $image->size_bytes,
            ],
        ], 201);
    }

    public function file(Request $request, Image $image): StreamedResponse|JsonResponse
    {
        $this->authorize('view', $image);

        $disk = Storage::disk('chat_images');
        if (! $disk->exists($image->disk_path)) {
            return response()->json(['message' => 'Файл не знайдено.'], 404);
        }

        return $disk->response($image->disk_path, $image->file_name, [
            'Content-Type' => $image->mime,
        ]);
    }
}

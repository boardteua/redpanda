<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ChatMessage;
use App\Models\Image;
use App\Models\User;
use App\Services\Moderation\UserPostingGate;
use App\Support\ChatImageUploadValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UserAvatarController extends Controller
{
    public function store(Request $request, UserPostingGate $postingGate): JsonResponse
    {
        ChatImageUploadValidation::validateUploadedImage($request);

        /** @var User $user */
        $user = $request->user();

        if ($user->guest) {
            return response()->json([
                'message' => 'Гості не можуть завантажувати аватарку.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($user->isChatUploadDisabled()) {
            return response()->json([
                'message' => 'Завантаження зображень для вашого облікового запису вимкнено модератором.',
            ], Response::HTTP_FORBIDDEN);
        }

        $postingGate->ensureCanPost($user);

        $file = $request->file('image');
        $now = time();
        $path = $file->store($user->id.'/avatars', 'chat_images');
        if ($path === false) {
            return response()->json(['message' => 'Не вдалося зберегти файл.'], 500);
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $displayName = $original !== '' ? mb_substr($original, 0, 200) : 'avatar';

        DB::transaction(function () use ($user, $file, $path, $displayName, $now): void {
            $previousId = $user->avatar_image_id;

            $image = Image::query()->create([
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'disk_path' => $path,
                'file_name' => $displayName,
                'mime' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
                'size_bytes' => (int) $file->getSize(),
                'date_sent' => $now,
            ]);

            $user->forceFill(['avatar_image_id' => $image->id])->save();

            if ($previousId !== null && (int) $previousId !== (int) $image->id) {
                $this->deleteAvatarImageIfOrphaned((int) $previousId);
            }
        });

        return UserResource::make($user->fresh())
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function deleteAvatarImageIfOrphaned(int $imageId): void
    {
        if (User::query()->where('avatar_image_id', $imageId)->exists()) {
            return;
        }

        if (ChatMessage::query()->where('file', $imageId)->exists()) {
            return;
        }

        $row = Image::query()->find($imageId);
        if ($row === null) {
            return;
        }

        Storage::disk('chat_images')->delete($row->disk_path);
        $row->delete();
    }
}

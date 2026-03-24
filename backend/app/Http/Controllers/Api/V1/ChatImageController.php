<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Services\Moderation\UserPostingGate;
use App\Support\ChatImageUploadValidation;
use App\Support\ChatUploadedImageInspector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatImageController extends Controller
{
    /**
     * Останні завантажені зображення поточного користувача (для модалу «мої зображення»).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->guest) {
            return response()->json([
                'message' => 'Гості не мають доступу до бібліотеки зображень чату.',
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 24);
        $perPage = min(max($perPage, 1), 60);

        $paginator = Image::query()
            ->where('user_id', $user->id)
            ->orderByDesc('date_sent')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (Image $image): array {
            return [
                'id' => $image->id,
                'url' => route('api.v1.chat-images.file', ['image' => $image->id], true),
                'mime' => $image->mime,
                'size_bytes' => $image->size_bytes,
                'file_name' => $image->file_name,
                'date_sent' => $image->date_sent,
            ];
        });

        return response()->json($paginator);
    }

    public function store(Request $request, UserPostingGate $postingGate): JsonResponse
    {
        ChatImageUploadValidation::validateUploadedImage($request);

        $user = $request->user();
        if ($user->guest) {
            return response()->json([
                'message' => 'Гості не можуть завантажувати зображення в чат.',
            ], 403);
        }
        if ($user->isChatUploadDisabled()) {
            return response()->json([
                'message' => 'Завантаження зображень для вашого облікового запису вимкнено модератором.',
            ], 403);
        }
        $postingGate->ensureCanPost($user);
        $file = $request->file('image');
        $inspected = ChatUploadedImageInspector::inspectOrFail(
            $file,
            ChatUploadedImageInspector::CHAT_IMAGE_MAX_DIMENSION,
            ChatUploadedImageInspector::CHAT_IMAGE_MAX_DIMENSION,
        );

        $now = time();
        $path = $file->store((string) $user->id, 'chat_images');
        if ($path === false) {
            Log::warning('chat_image_store_failed', [
                'user_id' => $user->id,
                'reason' => 'store_returned_false',
            ]);

            return response()->json([
                'message' => 'Не вдалося зберегти файл на сервері. Перевірте права на каталог storage/app/chat-images або зверніться до адміністратора.',
            ], 503);
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $displayName = ChatUploadedImageInspector::sanitizeDisplayBasename(
            $original !== '' ? $original : '',
            'image',
        );

        try {
            $image = Image::query()->create([
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'disk_path' => $path,
                'file_name' => $displayName,
                'mime' => $inspected['mime'],
                'size_bytes' => (int) $file->getSize(),
                'date_sent' => $now,
            ]);
        } catch (Throwable $e) {
            Log::error('chat_image_db_create_failed', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            Storage::disk('chat_images')->delete($path);

            return response()->json([
                'message' => 'Не вдалося зареєструвати зображення. Спробуйте ще раз або зверніться до адміністратора.',
            ], 503);
        }

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

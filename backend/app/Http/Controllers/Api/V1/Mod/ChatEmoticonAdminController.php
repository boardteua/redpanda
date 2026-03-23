<?php

namespace App\Http\Controllers\Api\V1\Mod;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatEmoticonAdminResource;
use App\Models\ChatEmoticon;
use App\Services\Chat\ChatEmoticonFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ChatEmoticonAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = ChatEmoticon::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => ChatEmoticonAdminResource::collection($rows)->resolve(),
        ], Response::HTTP_OK, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request, ChatEmoticonFileService $files): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('chat_emoticons', 'code')],
            'display_name' => ['required', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:99999999'],
            'is_active' => ['sometimes', 'boolean'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['required', 'file', 'max:512', 'mimetypes:image/gif,image/png,image/webp'],
        ]);

        [$basename] = $files->storeUploaded($request->file('file'));

        $row = ChatEmoticon::query()->create([
            'code' => $validated['code'],
            'display_name' => $validated['display_name'],
            'file_name' => $basename,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'keywords' => $validated['keywords'] ?? null,
        ]);

        return response()->json([
            'data' => (new ChatEmoticonAdminResource($row))->resolve(),
        ], Response::HTTP_CREATED, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, ChatEmoticon $emoticon, ChatEmoticonFileService $files): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:99999999'],
            'is_active' => ['sometimes', 'boolean'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['sometimes', 'file', 'max:512', 'mimetypes:image/gif,image/png,image/webp'],
        ]);

        $oldFile = $emoticon->file_name;

        if ($request->hasFile('file')) {
            [$basename] = $files->storeUploaded($request->file('file'));
            $emoticon->file_name = $basename;
        }

        if (array_key_exists('display_name', $validated)) {
            $emoticon->display_name = $validated['display_name'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $emoticon->sort_order = (int) $validated['sort_order'];
        }
        if (array_key_exists('is_active', $validated)) {
            $emoticon->is_active = (bool) $validated['is_active'];
        }
        if (array_key_exists('keywords', $validated)) {
            $emoticon->keywords = $validated['keywords'];
        }

        $emoticon->save();

        if ($request->hasFile('file') && $oldFile !== $emoticon->file_name) {
            $files->deleteFileIfUnused($oldFile, (int) $emoticon->id);
        }

        return response()->json([
            'data' => (new ChatEmoticonAdminResource($emoticon->fresh()))->resolve(),
        ], Response::HTTP_OK, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(ChatEmoticon $emoticon, ChatEmoticonFileService $files): JsonResponse
    {
        $id = (int) $emoticon->id;
        $basename = $emoticon->file_name;
        $emoticon->delete();
        $files->deleteFileIfUnused($basename, $id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

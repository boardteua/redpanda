<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ChatSilentModeUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChatSettingsRequest;
use App\Http\Resources\ChatSettingsResource;
use App\Models\ChatSetting;
use Illuminate\Http\JsonResponse;

class ChatSettingsController extends Controller
{
    /**
     * Публічно безпечний зріз для UI (T44): поріг N і область лічби. Авторизовані користувачі (у т.ч. гість).
     */
    public function show(): JsonResponse
    {
        $row = ChatSetting::current();

        return response()->json([
            'data' => new ChatSettingsResource($row),
        ]);
    }

    public function update(UpdateChatSettingsRequest $request): JsonResponse
    {
        $row = ChatSetting::current();
        $validated = $request->validated();

        if (
            isset($validated['public_message_count_scope'])
            && $validated['public_message_count_scope'] === ChatSetting::SCOPE_ALL_PUBLIC_ROOMS
        ) {
            $validated['message_count_room_id'] = null;
        }

        $row->fill($validated);
        $row->save();

        $fresh = $row->fresh();
        if (array_key_exists('silent_mode', $validated)) {
            broadcast(new ChatSilentModeUpdated((bool) $fresh->silent_mode));
        }

        return response()->json([
            'data' => new ChatSettingsResource($fresh),
        ]);
    }
}

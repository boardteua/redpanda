<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\RoomReadState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomReadController extends Controller
{
    /**
     * Оновити останній прочитаний post_id у кімнаті (монотонно, лише вгору).
     */
    public function store(Request $request, Room $room): JsonResponse
    {
        $this->authorize('interact', $room);

        $validated = $request->validate([
            'last_read_post_id' => ['required', 'integer', 'min:1'],
        ]);

        $uid = (int) $request->user()->id;
        $postId = (int) $validated['last_read_post_id'];

        $visible = ChatMessage::query()
            ->visibleInRoomForUser($room, $uid)
            ->where('post_id', $postId)
            ->exists();

        if (! $visible) {
            return response()->json([
                'message' => 'Повідомлення не знайдено в цій кімнаті.',
            ], 422);
        }

        $row = RoomReadState::query()->firstOrNew([
            'user_id' => $uid,
            'room_id' => $room->room_id,
        ]);

        $prev = (int) ($row->last_read_post_id ?? 0);
        if ($postId > $prev) {
            $row->last_read_post_id = $postId;
            $row->save();
        }

        return response()->json([
            'data' => [
                'room_id' => (int) $room->room_id,
                'last_read_post_id' => (int) $row->last_read_post_id,
            ],
        ]);
    }
}

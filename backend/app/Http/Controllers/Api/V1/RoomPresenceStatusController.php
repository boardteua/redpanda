<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\PresenceStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Chat\RoomPresenceStatusCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomPresenceStatusController extends Controller
{
    /**
     * Статуси присутності для переліку user_id (учасники з Echo presence).
     */
    public function index(Request $request, Room $room): JsonResponse
    {
        $this->authorize('interact', $room);

        $validated = $request->validate([
            'user_ids' => ['required', 'string', 'max:4096'],
        ]);

        $parts = preg_split('/[\s,]+/', $validated['user_ids'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ids = [];
        foreach ($parts as $p) {
            if (count($ids) >= 100) {
                break;
            }
            if (preg_match('/^\d+$/', $p) !== 1) {
                continue;
            }
            $n = (int) $p;
            if ($n >= 1) {
                $ids[$n] = true;
            }
        }

        $uniqueIds = array_keys($ids);
        $map = RoomPresenceStatusCache::getMany((int) $room->room_id, $uniqueIds);

        return response()->json(['data' => $map]);
    }

    /**
     * Оновити власний статус у кімнаті (розсилається на presence-канал).
     */
    public function store(Request $request, Room $room): JsonResponse
    {
        $this->authorize('interact', $room);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(RoomPresenceStatusCache::STATUSES)],
        ]);

        $uid = (int) $request->user()->id;
        $status = $validated['status'];
        $roomId = (int) $room->room_id;

        $prev = RoomPresenceStatusCache::put($roomId, $uid, $status);
        if ($prev !== $status) {
            broadcast(new PresenceStatusUpdated($roomId, $uid, $status));
        }

        return response()->json([
            'data' => [
                'room_id' => $roomId,
                'user_id' => $uid,
                'status' => $status,
            ],
        ]);
    }
}

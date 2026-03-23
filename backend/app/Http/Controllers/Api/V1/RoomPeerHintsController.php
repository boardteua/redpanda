<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Компактні підказки для списку «Люди» (T49): стать, якщо дозволено політикою приватності.
 * Для персоналу модерації — також прапорець `chat_upload_disabled` цілі (без розкриття іншим).
 *
 * Гість-переглядач не отримує підказок (порожній об’єкт).
 */
class RoomPeerHintsController extends Controller
{
    private const ALLOWED_SEX = ['male', 'female', 'other'];

    public function index(Request $request, Room $room): JsonResponse
    {
        $this->authorize('interact', $room);

        $validated = $request->validate([
            'user_ids' => ['required', 'string', 'max:4096'],
        ]);

        /** @var User $viewer */
        $viewer = $request->user();

        if ($viewer->guest) {
            return response()->json(['data' => (object) []]);
        }

        $parts = preg_split('/[\s,]+/', $validated['user_ids'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $uniqueIds = [];
        foreach ($parts as $p) {
            if (count($uniqueIds) >= 100) {
                break;
            }
            if (preg_match('/^\d+$/', $p) !== 1) {
                continue;
            }
            $n = (int) $p;
            if ($n >= 1) {
                $uniqueIds[$n] = true;
            }
        }

        $viewerId = (int) $viewer->id;
        $out = [];

        foreach (array_keys($uniqueIds) as $uid) {
            if ($uid === $viewerId) {
                continue;
            }

            $target = User::query()->find($uid);
            if ($target === null || $target->guest) {
                continue;
            }

            $entry = [];

            if (! $target->profile_sex_hidden) {
                $sex = $target->profile_sex;
                if (is_string($sex) && in_array($sex, self::ALLOWED_SEX, true)) {
                    $entry['sex'] = $sex;
                }
            }

            if ($viewer->canModerate()) {
                $entry['chat_upload_disabled'] = (bool) $target->chat_upload_disabled;
            }

            if ($entry !== []) {
                $out[(string) $uid] = $entry;
            }
        }

        return response()->json(['data' => (object) $out]);
    }
}

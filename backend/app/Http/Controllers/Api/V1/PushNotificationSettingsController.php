<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePushNotificationSettingsRequest;
use App\Models\User;
use App\Models\UserWebPushPrivatePeerMute;
use App\Models\UserWebPushRoomMute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushNotificationSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->requireNonGuestUser($request);

        return response()->json([
            'data' => $this->serializeSettings($user),
        ]);
    }

    public function update(UpdatePushNotificationSettingsRequest $request): JsonResponse
    {
        $user = $this->requireNonGuestUser($request);
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            if (array_key_exists('web_push_enabled', $validated)) {
                $user->web_push_master_enabled = (bool) $validated['web_push_enabled'];
                $user->save();
            }
            if (array_key_exists('muted_room_ids', $validated)) {
                UserWebPushRoomMute::query()->where('user_id', $user->id)->delete();
                foreach ($validated['muted_room_ids'] as $rid) {
                    UserWebPushRoomMute::query()->create([
                        'user_id' => $user->id,
                        'room_id' => (int) $rid,
                    ]);
                }
            }
            if (array_key_exists('muted_private_peer_ids', $validated)) {
                UserWebPushPrivatePeerMute::query()->where('user_id', $user->id)->delete();
                foreach ($validated['muted_private_peer_ids'] as $pid) {
                    UserWebPushPrivatePeerMute::query()->create([
                        'user_id' => $user->id,
                        'peer_user_id' => (int) $pid,
                    ]);
                }
            }
        });

        return response()->json([
            'data' => $this->serializeSettings($user->fresh()),
        ]);
    }

    private function requireNonGuestUser(Request $request): User
    {
        $user = $request->user();
        abort_if($user === null || $user->guest, 403, 'Гості не керують web push.');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSettings(User $user): array
    {
        $roomMutes = UserWebPushRoomMute::query()
            ->where('user_id', $user->id)
            ->with('room:room_id,room_name,slug')
            ->orderBy('room_id')
            ->get();

        $peerMutes = UserWebPushPrivatePeerMute::query()
            ->where('user_id', $user->id)
            ->with('peer:id,user_name')
            ->orderBy('peer_user_id')
            ->get();

        return [
            'web_push_enabled' => (bool) $user->web_push_master_enabled,
            'muted_rooms' => $roomMutes->map(function ($row) {
                $room = $row->room;

                return [
                    'room_id' => (int) $row->room_id,
                    'room_name' => $room !== null ? (string) $room->room_name : '',
                    'slug' => $room !== null ? (string) ($room->slug ?? '') : '',
                ];
            })->values()->all(),
            'muted_private_peers' => $peerMutes->map(function ($row) {
                $peer = $row->peer;

                return [
                    'user_id' => (int) $row->peer_user_id,
                    'user_name' => $peer !== null ? (string) $peer->user_name : '',
                ];
            })->values()->all(),
        ];
    }
}

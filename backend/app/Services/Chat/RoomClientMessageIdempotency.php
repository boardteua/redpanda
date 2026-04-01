<?php

namespace App\Services\Chat;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\User;
use App\Support\DatabaseDuplicateKey;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

final class RoomClientMessageIdempotency
{
    public function findDuplicateResponse(User $user, Room $room, string $clientId): ?JsonResponse
    {
        $existing = ChatMessage::query()
            ->where('user_id', $user->id)
            ->where('client_message_id', $clientId)
            ->first();

        if ($existing === null) {
            return null;
        }

        return $this->buildDuplicateResponse($existing, $room);
    }

    public function resolveAfterDuplicateKey(
        QueryException $exception,
        User $user,
        Room $room,
        string $clientId,
        bool $checkPrivateMessageConflict = false,
    ): JsonResponse {
        if (! DatabaseDuplicateKey::is($exception)) {
            throw $exception;
        }

        $retry = ChatMessage::query()
            ->where('user_id', $user->id)
            ->where('client_message_id', $clientId)
            ->first();

        if ($retry !== null) {
            return $this->buildDuplicateResponse($retry, $room);
        }

        if ($checkPrivateMessageConflict) {
            if (PrivateMessage::query()
                ->where('sender_id', $user->id)
                ->where('client_message_id', $clientId)
                ->exists()) {
                return response()->json([
                    'message' => 'client_message_id already used for a private message.',
                ], 422);
            }
        }

        throw $exception;
    }

    private function buildDuplicateResponse(ChatMessage $message, Room $room): JsonResponse
    {
        if ((int) $message->post_roomid !== (int) $room->room_id) {
            return response()->json([
                'message' => 'client_message_id already used for another room.',
            ], 422);
        }

        return ChatMessageResource::make($message)
            ->additional([
                'meta' => [
                    'duplicate' => true,
                    'slash' => ['name' => null, 'recognized' => false, 'client_only' => false],
                ],
            ])
            ->response()
            ->setStatusCode(200);
    }
}

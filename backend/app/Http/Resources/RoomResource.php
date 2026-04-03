<?php

namespace App\Http\Resources;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Room */
class RoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uid = $request->user()?->id;
        $creatorId = $this->created_by_user_id;
        $isCreator = $uid !== null && $creatorId !== null && (int) $creatorId === (int) $uid;

        $slugSource = $this->resource->slugBindingSource ?? null;
        $slugRedirect = is_string($slugSource)
            && $slugSource !== ''
            && strtolower($slugSource) !== strtolower((string) $this->slug);

        return [
            'room_id' => $this->room_id,
            'room_name' => $this->room_name,
            'slug' => $this->slug,
            'slug_redirect' => $slugRedirect,
            'topic' => $this->topic,
            'access' => (int) $this->access,
            'ai_bot_enabled' => (bool) ($this->ai_bot_enabled ?? true),
            'created_by_user_id' => $creatorId !== null ? (int) $creatorId : null,
            'creator_is_chat_admin' => $this->when(
                $this->relationLoaded('creator') && $this->creator !== null,
                fn (): bool => $this->creator->isChatAdmin(),
            ),
            'created_by_me' => $isCreator,
            'is_room_moderator' => $isCreator,
            'messages_count' => isset($this->messages_count) ? (int) $this->messages_count : null,
        ];
    }
}

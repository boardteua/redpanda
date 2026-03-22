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
        return [
            'room_id' => $this->room_id,
            'room_name' => $this->room_name,
            'topic' => $this->topic,
            'access' => (int) $this->access,
            'created_by_user_id' => $this->created_by_user_id !== null ? (int) $this->created_by_user_id : null,
            'messages_count' => isset($this->messages_count) ? (int) $this->messages_count : null,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\ChatSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChatSetting */
class ChatSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'room_create_min_public_messages' => (int) $this->room_create_min_public_messages,
            'public_message_count_scope' => (string) $this->public_message_count_scope,
            'message_count_room_id' => $this->message_count_room_id,
        ];
    }
}

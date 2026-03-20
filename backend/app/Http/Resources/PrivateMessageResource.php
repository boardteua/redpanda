<?php

namespace App\Http\Resources;

use App\Models\PrivateMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PrivateMessage */
class PrivateMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => (int) $this->sender_id,
            'recipient_id' => (int) $this->recipient_id,
            'body' => $this->body,
            'sent_at' => (int) $this->sent_at,
            'sent_time' => $this->sent_time,
            'client_message_id' => $this->client_message_id,
        ];
    }
}

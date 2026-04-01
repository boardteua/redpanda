<?php

namespace App\Http\Resources;

use App\Models\PrivateMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin PrivateMessage */
class PrivateMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageId = $this->image_id !== null ? (int) $this->image_id : 0;

        return [
            'id' => $this->id,
            'sender_id' => (int) $this->sender_id,
            'recipient_id' => (int) $this->recipient_id,
            'body' => $this->body,
            'sent_at' => (int) $this->sent_at,
            'sent_time' => $this->sent_time,
            'client_message_id' => $this->client_message_id,
            'image' => $this->when(
                $imageId > 0,
                fn () => [
                    'id' => $imageId,
                    'url' => URL::route('api.v1.chat-images.file', ['image' => $imageId], true),
                ],
            ),
        ];
    }
}

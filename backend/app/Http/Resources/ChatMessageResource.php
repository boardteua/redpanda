<?php

namespace App\Http\Resources;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

/** @mixin ChatMessage */
class ChatMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'post_date' => (int) $this->post_date,
            'post_edited_at' => $this->post_edited_at !== null ? (int) $this->post_edited_at : null,
            'post_time' => $this->post_time,
            'post_user' => $this->post_user,
            'post_message' => $this->post_message,
            'post_style' => $this->post_style,
            'post_color' => $this->post_color,
            'post_roomid' => (int) $this->post_roomid,
            'type' => $this->type,
            'recipient_user_id' => $this->when(
                $this->type === 'inline_private' && $this->post_target !== null && $this->post_target !== '',
                fn () => (int) $this->post_target,
            ),
            'client_message_id' => $this->client_message_id,
            'avatar' => $this->avatar,
            'file' => (int) $this->file,
            'image' => $this->when(
                (int) $this->file > 0,
                fn () => [
                    'id' => (int) $this->file,
                    'url' => URL::route('api.v1.chat-images.file', ['image' => (int) $this->file], true),
                ],
            ),
            'can_edit' => Gate::forUser($request->user())->allows('update', $this->resource),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Chat\RoomReplyPrefixMentionParser;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

/** @mixin ChatMessage */
class ChatMessageResource extends JsonResource
{
    /** Ключ атрибута запиту з батч-мапою can_edit/can_delete (T105). */
    public const ABILITY_MAP_REQUEST_KEY = 'chat_message_ability_map';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, array{can_edit: bool, can_delete: bool}>|null $abilityMap */
        $abilityMap = $request->attributes->get(self::ABILITY_MAP_REQUEST_KEY);

        return [
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'post_date' => (int) $this->post_date,
            'post_edited_at' => $this->post_edited_at !== null ? (int) $this->post_edited_at : null,
            'post_deleted_at' => $this->post_deleted_at !== null ? (int) $this->post_deleted_at : null,
            'post_time' => $this->post_time,
            'post_user' => $this->post_user,
            'post_message' => ($this->post_deleted_at !== null && $this->archived_from_room_id === null)
                ? ''
                : $this->post_message,
            'post_style' => $this->post_style,
            'post_color' => $this->post_color,
            'post_roomid' => (int) $this->post_roomid,
            'archived_from_room_id' => $this->when(
                $this->archived_from_room_id !== null,
                fn () => (int) $this->archived_from_room_id,
            ),
            'archived_room_name' => $this->when(
                $this->archived_room_name !== null && $this->archived_room_name !== '',
                fn () => (string) $this->archived_room_name,
            ),
            'type' => $this->type,
            'system_kind' => $this->type === 'system' ? $this->system_kind : null,
            'target_room_id' => $this->type === 'system' && $this->system_target_room_id !== null
                ? (int) $this->system_target_room_id
                : null,
            'action_label' => $this->type === 'system' ? $this->system_action_label : null,
            'recipient_user_id' => $this->when(
                $this->type === 'inline_private' && $this->post_target !== null && $this->post_target !== '',
                fn () => (int) $this->post_target,
            ),
            'mentioned_user_ids' => RoomReplyPrefixMentionParser::mentionedUserIds($this->resource),
            'client_message_id' => $this->client_message_id,
            'avatar' => $this->avatar,
            'file' => (int) $this->file,
            'image' => $this->when(
                ($this->post_deleted_at === null || $this->archived_from_room_id !== null) && (int) $this->file > 0,
                fn () => [
                    'id' => (int) $this->file,
                    'url' => URL::route('api.v1.chat-images.file', ['image' => (int) $this->file], true),
                ],
            ),
            'can_edit' => $this->resolveCanEdit($request, $abilityMap),
            'can_delete' => $this->resolveCanDelete($request, $abilityMap),
        ];
    }

    /**
     * @param  array<int, array{can_edit: bool, can_delete: bool}>|null  $abilityMap
     */
    private function resolveCanEdit(Request $request, ?array $abilityMap): bool
    {
        $postId = (int) $this->post_id;
        if (is_array($abilityMap) && array_key_exists($postId, $abilityMap)) {
            return $abilityMap[$postId]['can_edit'];
        }

        return Gate::forUser($request->user())->allows('update', $this->resource);
    }

    /**
     * @param  array<int, array{can_edit: bool, can_delete: bool}>|null  $abilityMap
     */
    private function resolveCanDelete(Request $request, ?array $abilityMap): bool
    {
        if ($this->post_deleted_at !== null) {
            return false;
        }

        $postId = (int) $this->post_id;
        if (is_array($abilityMap) && array_key_exists($postId, $abilityMap)) {
            return $abilityMap[$postId]['can_delete'];
        }

        return Gate::forUser($request->user())->allows('delete', $this->resource);
    }
}

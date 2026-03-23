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
        /** @var ChatSetting $m */
        $m = $this->resource;

        return [
            'room_create_min_public_messages' => (int) $this->room_create_min_public_messages,
            'public_message_count_scope' => (string) $this->public_message_count_scope,
            'message_count_room_id' => $this->message_count_room_id,
            'slash_command_max_per_window' => (int) $this->slash_command_max_per_window,
            'slash_command_window_seconds' => (int) $this->slash_command_window_seconds,
            'mod_slash_default_mute_minutes' => (int) $this->mod_slash_default_mute_minutes,
            'mod_slash_default_kick_minutes' => (int) $this->mod_slash_default_kick_minutes,
            'silent_mode' => (bool) $this->silent_mode,
            'sound_on_every_post' => (bool) ($m->sound_on_every_post ?? false),
            'landing_settings' => $m->resolvedLandingSettings(),
            'registration_flags' => $m->resolvedRegistrationFlags(),
        ];
    }
}

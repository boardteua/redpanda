<?php

namespace App\Http\Requests;

use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u !== null && $u->isChatAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_create_min_public_messages' => ['sometimes', 'integer', 'min:0', 'max:99999999'],
            'public_message_count_scope' => ['sometimes', 'string', Rule::in(ChatSetting::scopeValues())],
            'message_count_room_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('rooms', 'room_id')->where('access', Room::ACCESS_PUBLIC),
            ],
            'slash_command_max_per_window' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'slash_command_window_seconds' => ['sometimes', 'integer', 'min:10', 'max:86400'],
            'mod_slash_default_mute_minutes' => ['sometimes', 'integer', 'min:1', 'max:525600'],
            'mod_slash_default_kick_minutes' => ['sometimes', 'integer', 'min:1', 'max:525600'],
            'silent_mode' => ['sometimes', 'boolean'],
        ];
    }
}

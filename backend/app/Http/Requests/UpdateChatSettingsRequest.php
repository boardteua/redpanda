<?php

namespace App\Http\Requests;

use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'sound_on_every_post' => ['sometimes', 'boolean'],
            'landing_settings' => ['sometimes', 'array'],
            'landing_settings.page_title' => ['nullable', 'string', 'max:120'],
            'landing_settings.tagline' => ['nullable', 'string', 'max:200'],
            'landing_settings.news_title' => ['nullable', 'string', 'max:200'],
            'landing_settings.news_body' => ['nullable', 'string', 'max:8000'],
            'landing_settings.links' => ['sometimes', 'array', 'max:8'],
            'landing_settings.links.*.label' => ['nullable', 'string', 'max:100'],
            'landing_settings.links.*.url' => ['nullable', 'string', 'max:500'],
            'registration_flags' => ['sometimes', 'array'],
            'registration_flags.registration_open' => ['sometimes', 'boolean'],
            'registration_flags.show_social_login_buttons' => ['sometimes', 'boolean'],
            'registration_flags.min_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $landing = $this->input('landing_settings');
            if (! is_array($landing) || ! isset($landing['links']) || ! is_array($landing['links'])) {
                return;
            }
            foreach ($landing['links'] as $i => $link) {
                if (! is_array($link)) {
                    continue;
                }
                $url = $link['url'] ?? '';
                if (! is_string($url) || $url === '') {
                    continue;
                }
                if (! ChatSetting::isAcceptableLandingLinkUrl($url)) {
                    $v->errors()->add(
                        'landing_settings.links.'.$i.'.url',
                        'Посилання має бути http(s) або шлях від кореня (/...).',
                    );
                }
            }
        });
    }
}

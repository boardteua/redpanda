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
            'message_edit_window_hours' => ['sometimes', 'integer', 'min:0', 'max:8760'],
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
            'message_flood_enabled' => ['sometimes', 'boolean'],
            'message_flood_max_messages' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'message_flood_window_seconds' => ['sometimes', 'integer', 'min:1', 'max:86400'],
            'max_attachment_bytes' => [
                'sometimes',
                'integer',
                'min:1024',
                'max:'.ChatSetting::ADMIN_MAX_ATTACHMENT_BYTES_CAP,
            ],
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
            'transactional_mail_from_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'mail_template_overrides' => ['sometimes', 'array'],
            'mail_template_overrides.password_reset' => ['sometimes', 'array'],
            'mail_template_overrides.password_reset.subject' => ['nullable', 'string', 'max:200'],
            'mail_template_overrides.password_reset.html_body' => ['nullable', 'string', 'max:32000'],
            'mail_template_overrides.password_reset.text_body' => ['nullable', 'string', 'max:32000'],
            'mail_template_overrides.welcome_registered' => ['sometimes', 'array'],
            'mail_template_overrides.welcome_registered.subject' => ['nullable', 'string', 'max:200'],
            'mail_template_overrides.welcome_registered.html_body' => ['nullable', 'string', 'max:32000'],
            'mail_template_overrides.welcome_registered.text_body' => ['nullable', 'string', 'max:32000'],
            'mail_template_overrides.account_security_notice' => ['sometimes', 'array'],
            'mail_template_overrides.account_security_notice.subject' => ['nullable', 'string', 'max:200'],
            'mail_template_overrides.account_security_notice.html_body' => ['nullable', 'string', 'max:32000'],
            'mail_template_overrides.account_security_notice.text_body' => ['nullable', 'string', 'max:32000'],
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

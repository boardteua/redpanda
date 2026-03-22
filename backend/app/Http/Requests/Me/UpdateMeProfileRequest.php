<?php

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ! $user->guest;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sexValues = ['male', 'female', 'other', 'prefer_not'];

        $socialRule = ['sometimes', 'nullable', 'string', 'max:500'];

        return [
            'profile' => ['sometimes', 'array'],
            'profile.country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.age' => ['sometimes', 'nullable', 'integer', 'min:13', 'max:120'],
            'profile.sex' => ['sometimes', 'nullable', 'string', Rule::in($sexValues)],
            'profile.country_hidden' => ['sometimes', 'boolean'],
            'profile.region_hidden' => ['sometimes', 'boolean'],
            'profile.age_hidden' => ['sometimes', 'boolean'],
            'profile.sex_hidden' => ['sometimes', 'boolean'],
            'profile.occupation' => ['sometimes', 'nullable', 'string', 'max:191'],
            'profile.about' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'social_links' => ['sometimes', 'array'],
            'social_links.facebook' => $socialRule,
            'social_links.instagram' => $socialRule,
            'social_links.telegram' => $socialRule,
            'social_links.twitter' => $socialRule,
            'social_links.youtube' => $socialRule,
            'social_links.tiktok' => $socialRule,
            'social_links.discord' => $socialRule,
            'social_links.website' => $socialRule,

            'notification_sound_prefs' => ['sometimes', 'array'],
            'notification_sound_prefs.public_messages' => ['sometimes', 'boolean'],
            'notification_sound_prefs.mentions' => ['sometimes', 'boolean'],
            'notification_sound_prefs.private' => ['sometimes', 'boolean'],
            'notification_sound_prefs.volume_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}

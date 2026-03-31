<?php

namespace App\Http\Requests\Me;

use App\Support\Iso3166Alpha2Uk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ! $user->guest;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('profile')) {
            return;
        }
        $profile = $this->input('profile');
        if (! is_array($profile) || ! array_key_exists('country', $profile)) {
            return;
        }
        $country = $profile['country'];
        if ($country === null || $country === '') {
            $profile['country'] = null;
        } elseif (is_string($country)) {
            $c = strtoupper(trim($country));
            $profile['country'] = $c === '' ? null : $c;
        }
        $this->merge(['profile' => $profile]);
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
            'profile.country' => ['sometimes', 'nullable', 'string', Rule::in(Iso3166Alpha2Uk::codes())],
            'profile.region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.age' => ['sometimes', 'nullable', 'integer', 'min:13', 'max:120'],
            'profile.sex' => ['sometimes', 'nullable', 'string', Rule::in($sexValues)],
            'profile.country_hidden' => ['sometimes', 'boolean'],
            'profile.region_hidden' => ['sometimes', 'boolean'],
            'profile.age_hidden' => ['sometimes', 'boolean'],
            'profile.sex_hidden' => ['sometimes', 'boolean'],
            'profile.occupation' => ['sometimes', 'nullable', 'string', 'max:191'],
            'profile.occupation_hidden' => ['sometimes', 'boolean'],
            'profile.about' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'profile.about_hidden' => ['sometimes', 'boolean'],

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

            'chat_history_prefs' => ['sometimes', 'array'],
            'chat_history_prefs.room_history_chunk_size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'chat_history_prefs.private_history_chunk_size' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

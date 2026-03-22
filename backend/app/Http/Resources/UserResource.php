<?php

namespace App\Http\Resources;

use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->resolveChatRole();

        $base = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'guest' => (bool) $this->guest,
            'email' => $this->email,
            'avatar_url' => $this->resolveAvatarUrl(),
            'chat_role' => $role->value,
            'badge_color' => $role->badgeColor(),
        ];

        if ($this->guest) {
            return $base;
        }

        $base['message_edit_window_hours'] = (int) config('chat.message_edit_window_hours', 24);
        $base['can_create_room'] = Gate::forUser($this->resource)->allows('create', Room::class);

        $social = array_merge(User::defaultSocialLinkKeys(), $this->social_links ?? []);
        $sounds = array_replace(
            User::defaultNotificationSoundPrefs(),
            $this->notification_sound_prefs ?? []
        );

        return array_merge($base, [
            'profile' => [
                'country' => $this->profile_country,
                'region' => $this->profile_region,
                'age' => $this->profile_age,
                'sex' => $this->profile_sex,
                'country_hidden' => (bool) $this->profile_country_hidden,
                'region_hidden' => (bool) $this->profile_region_hidden,
                'age_hidden' => (bool) $this->profile_age_hidden,
                'sex_hidden' => (bool) $this->profile_sex_hidden,
                'occupation' => $this->profile_occupation,
                'about' => $this->profile_about,
            ],
            'social_links' => $social,
            'notification_sound_prefs' => $sounds,
        ]);
    }
}

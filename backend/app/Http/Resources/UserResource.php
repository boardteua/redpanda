<?php

namespace App\Http\Resources;

use App\Models\ChatSetting;
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
            'email' => $this->emailVisibleTo($request),
            'avatar_url' => $this->resolveAvatarUrl(),
            'chat_role' => $role->value,
            'badge_color' => $role->badgeColor(),
        ];

        if ($this->guest) {
            return $base;
        }

        $base['message_edit_window_hours'] = once(fn (): int => ChatSetting::current()->effectiveMessageEditWindowHours());
        $base['can_create_room'] = Gate::forUser($this->resource)->allows('create', Room::class);

        $social = array_merge(User::defaultSocialLinkKeys(), $this->social_links ?? []);
        $sounds = array_replace(
            User::defaultNotificationSoundPrefs(),
            $this->notification_sound_prefs ?? []
        );
        $historyPrefs = array_replace(
            User::defaultChatHistoryPrefs(),
            $this->chat_history_prefs ?? []
        );

        $auth = $request->user();
        $uploadFlag = [];
        if ($auth !== null && (int) $this->id === (int) $auth->id) {
            $uploadFlag['chat_upload_disabled'] = (bool) $this->chat_upload_disabled;
            $uploadFlag['presence_invisible'] = (bool) $this->presence_invisible;
            $uploadFlag['requires_password_setup'] = $this->requiresPasswordSetupAfterLegacyImport();
            $uploadFlag['chat_history_prefs'] = $historyPrefs;
        }

        return array_merge($base, $uploadFlag, [
            'profile' => $this->profilePayloadForRequest($request),
            'social_links' => $social,
            'notification_sound_prefs' => $sounds,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function emailVisibleTo(Request $request): ?string
    {
        $viewer = $request->user();
        if ($viewer === null) {
            return null;
        }
        if ((int) $viewer->id === (int) $this->id) {
            return $this->email;
        }
        if ($viewer->canModerate()) {
            return $this->email;
        }

        return null;
    }

    private function profilePayloadForRequest(Request $request): array
    {
        /** @var User $subject */
        $subject = $this->resource;
        $viewer = $request->user();
        $full = $viewer !== null
            && ((int) $viewer->id === (int) $subject->id || $viewer->canModerate());

        if ($full) {
            return [
                'country' => $subject->profile_country,
                'region' => $subject->profile_region,
                'age' => $subject->profile_age,
                'sex' => $subject->profile_sex,
                'country_hidden' => (bool) $subject->profile_country_hidden,
                'region_hidden' => (bool) $subject->profile_region_hidden,
                'age_hidden' => (bool) $subject->profile_age_hidden,
                'sex_hidden' => (bool) $subject->profile_sex_hidden,
                'occupation' => $subject->profile_occupation,
                'occupation_hidden' => (bool) $subject->profile_occupation_hidden,
                'about' => $subject->profile_about,
                'about_hidden' => (bool) $subject->profile_about_hidden,
            ];
        }

        $maskHidden = static function (bool $hidden, mixed $value): mixed {
            return $hidden ? null : $value;
        };

        return [
            'country' => $maskHidden((bool) $subject->profile_country_hidden, $subject->profile_country),
            'region' => $maskHidden((bool) $subject->profile_region_hidden, $subject->profile_region),
            'age' => $maskHidden((bool) $subject->profile_age_hidden, $subject->profile_age),
            'sex' => $maskHidden((bool) $subject->profile_sex_hidden, $subject->profile_sex),
            'country_hidden' => false,
            'region_hidden' => false,
            'age_hidden' => false,
            'sex_hidden' => false,
            'occupation' => $maskHidden((bool) $subject->profile_occupation_hidden, $subject->profile_occupation),
            'occupation_hidden' => false,
            'about' => $maskHidden((bool) $subject->profile_about_hidden, $subject->profile_about),
            'about_hidden' => false,
        ];
    }

    private function requiresPasswordSetupAfterLegacyImport(): bool
    {
        if ($this->guest || $this->legacy_imported_at === null) {
            return false;
        }
        if ($this->email === null || trim((string) $this->email) === '') {
            return false;
        }

        return $this->password === null || $this->password === '';
    }
}

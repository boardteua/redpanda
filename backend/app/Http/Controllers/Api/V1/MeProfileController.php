<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdateMeProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class MeProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        $user = $request->user();
        abort_if($user === null || $user->guest, 403, 'Гості не мають профілю для редагування.');

        return UserResource::make($user->fresh());
    }

    public function update(UpdateMeProfileRequest $request): UserResource
    {
        $user = $request->user();
        abort_if($user === null || $user->guest, 403, 'Гості не мають профілю для редагування.');

        /** @var User $model */
        $model = $user;
        $validated = $request->validated();

        if (isset($validated['profile']) && is_array($validated['profile'])) {
            $p = $validated['profile'];
            $map = [
                'country' => 'profile_country',
                'region' => 'profile_region',
                'age' => 'profile_age',
                'sex' => 'profile_sex',
                'country_hidden' => 'profile_country_hidden',
                'region_hidden' => 'profile_region_hidden',
                'age_hidden' => 'profile_age_hidden',
                'sex_hidden' => 'profile_sex_hidden',
                'occupation' => 'profile_occupation',
                'occupation_hidden' => 'profile_occupation_hidden',
                'about' => 'profile_about',
                'about_hidden' => 'profile_about_hidden',
            ];
            foreach ($map as $jsonKey => $column) {
                if (array_key_exists($jsonKey, $p)) {
                    $model->{$column} = $p[$jsonKey];
                }
            }
        }

        if (isset($validated['social_links']) && is_array($validated['social_links'])) {
            $merged = array_merge(User::defaultSocialLinkKeys(), $model->social_links ?? []);
            foreach (User::defaultSocialLinkKeys() as $key => $_) {
                if (array_key_exists($key, $validated['social_links'])) {
                    $merged[$key] = $validated['social_links'][$key] ?? '';
                }
            }
            $model->social_links = $merged;
        }

        if (isset($validated['notification_sound_prefs']) && is_array($validated['notification_sound_prefs'])) {
            $merged = array_replace(
                User::defaultNotificationSoundPrefs(),
                $model->notification_sound_prefs ?? [],
                $validated['notification_sound_prefs']
            );
            $model->notification_sound_prefs = $merged;
        }

        if (isset($validated['chat_history_prefs']) && is_array($validated['chat_history_prefs'])) {
            $merged = array_replace(
                User::defaultChatHistoryPrefs(),
                $model->chat_history_prefs ?? [],
                $validated['chat_history_prefs']
            );
            $model->chat_history_prefs = $merged;
        }

        $model->save();

        return UserResource::make($model->fresh());
    }
}

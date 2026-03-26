<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\UpdateSystemBotProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SystemBotProfileController extends Controller
{
    public function update(UpdateSystemBotProfileRequest $request): UserResource|JsonResponse
    {
        $bot = User::query()->where('is_system_bot', true)->orderBy('id')->first();
        if ($bot === null) {
            return response()->json(['message' => 'Системного бота не знайдено.'], 404);
        }

        $validated = $request->validated();
        if ($validated === []) {
            return response()->json(['message' => 'Немає полів для оновлення.'], 422);
        }

        if (array_key_exists('user_name', $validated)) {
            $bot->user_name = $validated['user_name'];
        }

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
                    $bot->{$column} = $p[$jsonKey];
                }
            }
        }

        if (isset($validated['social_links']) && is_array($validated['social_links'])) {
            $merged = array_merge(User::defaultSocialLinkKeys(), $bot->social_links ?? []);
            foreach (User::defaultSocialLinkKeys() as $key => $_) {
                if (array_key_exists($key, $validated['social_links'])) {
                    $merged[$key] = $validated['social_links'][$key] ?? '';
                }
            }
            $bot->social_links = $merged;
        }

        if (isset($validated['notification_sound_prefs']) && is_array($validated['notification_sound_prefs'])) {
            $bot->notification_sound_prefs = array_replace(
                User::defaultNotificationSoundPrefs(),
                $bot->notification_sound_prefs ?? [],
                $validated['notification_sound_prefs']
            );
        }

        $bot->save();

        return UserResource::make($bot->fresh());
    }
}

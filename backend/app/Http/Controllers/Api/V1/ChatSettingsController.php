<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ChatSilentModeUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChatSettingsRequest;
use App\Http\Resources\ChatSettingsResource;
use App\Models\ChatSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ChatSettingsController extends Controller
{
    /**
     * Публічно безпечний зріз для UI (T44): поріг N і область лічби. Авторизовані користувачі (у т.ч. гість).
     */
    public function show(): JsonResponse
    {
        $row = ChatSetting::current();

        return response()->json([
            'data' => new ChatSettingsResource($row),
        ]);
    }

    public function update(UpdateChatSettingsRequest $request): JsonResponse
    {
        $row = ChatSetting::current();
        $validated = $request->validated();

        $landingNormalized = null;
        if (array_key_exists('landing_settings', $validated)) {
            $landingNormalized = ChatSetting::normalizeLandingSettings($validated['landing_settings']);
            unset($validated['landing_settings']);
        }
        $registrationNormalized = null;
        if (array_key_exists('registration_flags', $validated)) {
            $registrationNormalized = ChatSetting::normalizeRegistrationFlags($validated['registration_flags']);
            unset($validated['registration_flags']);
        }
        $mailTemplatesNormalized = null;
        if (array_key_exists('mail_template_overrides', $validated)) {
            $mailTemplatesNormalized = ChatSetting::normalizeMailTemplateOverrides($validated['mail_template_overrides']);
            unset($validated['mail_template_overrides']);
        }

        if (
            isset($validated['public_message_count_scope'])
            && $validated['public_message_count_scope'] === ChatSetting::SCOPE_ALL_PUBLIC_ROOMS
        ) {
            $validated['message_count_room_id'] = null;
        }

        $row->fill($validated);
        if ($landingNormalized !== null) {
            $row->landing_settings = $landingNormalized;
        }
        if ($registrationNormalized !== null) {
            $row->registration_flags = $registrationNormalized;
        }
        if ($mailTemplatesNormalized !== null) {
            $row->mail_template_overrides = $mailTemplatesNormalized;
        }
        $row->save();

        if (array_key_exists('proxycheck_enabled', $validated)) {
            Cache::forget('chat_settings:proxycheck_enabled');
        }

        $fresh = $row->fresh();
        if (array_key_exists('silent_mode', $validated)) {
            broadcast(new ChatSilentModeUpdated((bool) $fresh->silent_mode));
        }

        return response()->json([
            'data' => new ChatSettingsResource($fresh),
        ]);
    }
}

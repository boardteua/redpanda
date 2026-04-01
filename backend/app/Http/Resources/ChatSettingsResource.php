<?php

namespace App\Http\Resources;

use App\Models\ChatSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChatSetting */
class ChatSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ChatSetting $m */
        $m = $this->resource;

        $configuredMax = max(1024, (int) ($m->max_attachment_bytes ?: ChatSetting::DEFAULT_MAX_ATTACHMENT_BYTES));

        $base = [
            'message_edit_window_hours' => $m->effectiveMessageEditWindowHours(),
            'room_create_min_public_messages' => (int) $this->room_create_min_public_messages,
            'public_message_count_scope' => (string) $this->public_message_count_scope,
            'message_count_room_id' => $this->message_count_room_id,
            'slash_command_max_per_window' => (int) $this->slash_command_max_per_window,
            'slash_command_window_seconds' => (int) $this->slash_command_window_seconds,
            'mod_slash_default_mute_minutes' => (int) $this->mod_slash_default_mute_minutes,
            'mod_slash_default_kick_minutes' => (int) $this->mod_slash_default_kick_minutes,
            'silent_mode' => (bool) $this->silent_mode,
            'sound_on_every_post' => (bool) ($m->sound_on_every_post ?? false),
            'message_flood_enabled' => (bool) ($m->message_flood_enabled ?? false),
            'message_flood_max_messages' => max(1, (int) ($m->message_flood_max_messages ?? 5)),
            'message_flood_window_seconds' => max(1, (int) ($m->message_flood_window_seconds ?? 10)),
            'max_attachment_bytes' => $configuredMax,
            'max_chat_image_upload_bytes' => $m->effectiveMaxChatImageUploadBytes(),
            'landing_settings' => $m->resolvedLandingSettings(),
            'registration_flags' => $m->resolvedRegistrationFlags(),
        ];

        if ($request->user()?->isChatAdmin()) {
            $base['proxycheck_enabled'] = (bool) ($m->proxycheck_enabled ?? true);
            $base['transactional_mail_from_name'] = $m->transactional_mail_from_name;
            $base['mail_template_overrides'] = $m->resolvedMailTemplateOverrides();

            $base['ai_llm_enabled'] = (bool) ($m->ai_llm_enabled ?? false);
            $base['ai_gemini_model_flash'] = $m->ai_gemini_model_flash;
            $base['ai_gemini_model_flash_lite'] = $m->ai_gemini_model_flash_lite;
            $base['ai_gemini_model_pro'] = $m->ai_gemini_model_pro;
            $base['ai_gemini_model_image'] = $m->ai_gemini_model_image;
            $base['ai_gemini_model_effective'] = [
                'flash' => $m->effectiveGeminiModelFlash(),
                'flash_lite' => $m->effectiveGeminiModelFlashLite(),
                'pro' => $m->effectiveGeminiModelPro(),
                'image' => $m->effectiveGeminiModelImage(),
            ];
            $base['ai_bot_persona_prompt'] = $m->ai_bot_persona_prompt;
            $base['ai_bot_persona_revision'] = max(1, (int) ($m->ai_bot_persona_revision ?? 1));
            $base['ai_bot_persona_prompt_default'] = ChatSetting::defaultPersonaPromptFromConfig();
            $base['ai_summary_window_hours'] = max(1, (int) ($m->ai_summary_window_hours ?? 24));
            $base['ai_summary_rollup_chunk_size'] = max(1, (int) ($m->ai_summary_rollup_chunk_size ?? 20));
            $base['ai_summary_max_chars'] = max(256, (int) ($m->ai_summary_max_chars ?? 4000));
            $base['ai_bot_reply_delay_min_ms'] = max(0, (int) ($m->ai_bot_reply_delay_min_ms ?? 1200));
            $base['ai_bot_reply_delay_max_ms'] = max(0, (int) ($m->ai_bot_reply_delay_max_ms ?? 3000));
            $base['ai_bot_room_max_replies_per_window'] = max(1, (int) ($m->ai_bot_room_max_replies_per_window ?? 3));
            $base['ai_bot_room_window_seconds'] = max(5, (int) ($m->ai_bot_room_window_seconds ?? 300));
            $base['ai_bot_global_max_replies_per_window'] = max(1, (int) ($m->ai_bot_global_max_replies_per_window ?? 30));
            $base['ai_bot_global_window_seconds'] = max(5, (int) ($m->ai_bot_global_window_seconds ?? 300));
            $base['ai_bot_max_reply_chars'] = max(80, (int) ($m->ai_bot_max_reply_chars ?? 500));
            $base['ai_icebreaker_enabled'] = (bool) ($m->ai_icebreaker_enabled ?? false);
            $base['ai_icebreaker_idle_minutes'] = max(5, (int) ($m->ai_icebreaker_idle_minutes ?? 60));
            $base['ai_icebreaker_cooldown_minutes'] = max(5, (int) ($m->ai_icebreaker_cooldown_minutes ?? 180));
            $base['ai_icebreaker_jitter_minutes'] = max(0, (int) ($m->ai_icebreaker_jitter_minutes ?? 10));
        }

        return $base;
    }
}

<?php

namespace App\Services\Ai\RudaPanda;

use App\Jobs\GenerateRudaPandaRoomReplyJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class RudaPandaRoomResponder
{
    public function __construct(
        private readonly RudaPandaTriggerDetector $triggers,
        private readonly RudaPandaModelRouter $router,
        private readonly RudaPandaFollowupEvaluator $followupEvaluator,
        private readonly RudaPandaImageIntentDispatcher $imageIntentDispatcher,
    ) {}

    /**
     * @param  array<string, mixed>|null  $debug  When non-null, filled with dev-only decision trace (controller gates exposure).
     */
    public function maybeDispatchForMessage(ChatMessage $message, Room $room, ?array &$debug = null): void
    {
        $d = null;
        if ($debug !== null) {
            $d = [
                'version' => 1,
                'room_id' => (int) $room->room_id,
                'post_id' => (int) $message->post_id,
                'trigger' => null,
                'intent' => null,
                'followup_classifier' => null,
                'dispatched' => null,
                'note' => null,
            ];
        }

        $settings = ChatSetting::current();
        if (! $settings->ai_llm_enabled || ! ($room->ai_bot_enabled ?? true)) {
            if ($d !== null) {
                $d['note'] = 'llm_disabled_or_room_bot_off';
                $debug = $d;
            }

            return;
        }

        // Only react to regular public messages (not bot/system/private).
        if ($message->type !== 'public') {
            if ($d !== null) {
                $d['note'] = 'not_public_message';
                $debug = $d;
            }

            return;
        }
        if ((string) $message->post_color === 'system') {
            if ($d !== null) {
                $d['note'] = 'system_color';
                $debug = $d;
            }

            return;
        }

        $text = (string) $message->post_message;
        $mention = $this->triggers->shouldRespond($text);

        $followMeta = [];
        $followOk = false;
        if (! $mention) {
            $followOk = $this->followupEvaluator->shouldRespond($room, $message, $text, $followMeta);
            if ($d !== null) {
                $d['followup_classifier'] = $followMeta;
            }
        } elseif ($d !== null) {
            $d['followup_classifier'] = null;
        }

        if (! $mention && ! $followOk) {
            if ($d !== null) {
                $d['trigger'] = 'none';
                $d['note'] = 'no_mention_no_followup';
                $debug = $d;
            }

            return;
        }

        if ($d !== null) {
            if ($mention) {
                $d['trigger'] = 'mention';
            } elseif (($followMeta['path'] ?? '') === 'clarification_await') {
                $d['trigger'] = 'followup_clarification';
            } else {
                $d['trigger'] = 'followup_llm';
            }
        }

        $intent = $this->router->classifyIntent($text);
        if ($d !== null) {
            $d['intent'] = $intent->value;
        }

        if ($intent === RudaPandaIntent::Image) {
            $this->imageIntentDispatcher->dispatch($room, $message, $text, $d);
            if ($d !== null) {
                $debug = $d;
            }

            return;
        }

        // Cheap pre-limit to avoid queue bloat; real anti-flood is in scheduler (T179).
        $key = 'ruda-panda:gen:room:'.$room->room_id;
        $ok = RateLimiter::attempt($key, maxAttempts: 6, callback: static fn (): bool => true, decaySeconds: 60);
        if (! $ok) {
            if ($d !== null) {
                $d['note'] = 'rate_limited_room';
                $debug = $d;
            }

            return;
        }

        $idempotencyKey = (string) Str::uuid();

        GenerateRudaPandaRoomReplyJob::dispatch(
            roomId: (int) $room->room_id,
            triggerPostId: (int) $message->post_id,
            triggerUserId: (int) $message->user_id,
            triggerText: $text,
            idempotencyKey: $idempotencyKey,
        );

        if ($d !== null) {
            $d['dispatched'] = 'GenerateRudaPandaRoomReplyJob';
            $debug = $d;
        }
    }
}

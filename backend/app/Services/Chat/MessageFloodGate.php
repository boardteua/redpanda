<?php

namespace App\Services\Chat;

use App\Models\ChatSetting;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * T125: продуктовий антифлуд для POST повідомлень (кімната / приват) за налаштуваннями чату.
 * Ідемпотентні повтори з тим самим `client_message_id` не доходять сюди — ранній duplicate у контролері.
 */
class MessageFloodGate
{
    public const ERROR_CODE = 'message_flood_limit';

    /**
     * Персонал модерації, адмін чату та VIP не обмежуються (узгоджено з іншими лімітами чату).
     */
    public function isExempt(User $user): bool
    {
        if ($user->guest) {
            return false;
        }

        return $user->isChatAdmin() || $user->canModerate() || $user->isVip();
    }

    /**
     * Ковзне вікно: не більше N відправок за останні T секунд на користувача (глобально, кімната + приват разом).
     *
     * @return JsonResponse|null 429 або null, якщо можна продовжувати
     */
    public function ensureWithinLimit(User $user): ?JsonResponse
    {
        $settings = ChatSetting::current();
        if (! (bool) $settings->message_flood_enabled) {
            return null;
        }
        if ($this->isExempt($user)) {
            return null;
        }

        $max = max(1, min(65535, (int) $settings->message_flood_max_messages));
        $window = max(1, min(86400, (int) $settings->message_flood_window_seconds));
        $uid = (int) $user->id;
        $now = time();
        $cacheKey = 'chat_msg_flood_ts:'.$uid;
        $lockKey = 'chat_msg_flood_lock:'.$uid;

        try {
            return Cache::lock($lockKey, 5)->block(3, function () use ($cacheKey, $now, $window, $max): ?JsonResponse {
                /** @var list<int>|mixed $timestamps */
                $timestamps = Cache::get($cacheKey, []);
                if (! is_array($timestamps)) {
                    $timestamps = [];
                }
                $fresh = [];
                foreach ($timestamps as $ts) {
                    if (is_int($ts) || (is_string($ts) && ctype_digit((string) $ts))) {
                        $t = (int) $ts;
                        if ($now - $t < $window) {
                            $fresh[] = $t;
                        }
                    }
                }
                if (count($fresh) >= $max) {
                    return response()->json([
                        'message' => 'Занадто часті повідомлення. Зачекайте кілька секунд і спробуйте знову.',
                        'code' => self::ERROR_CODE,
                    ], 429);
                }
                $fresh[] = $now;
                Cache::put($cacheKey, $fresh, now()->addSeconds($window + 2));

                return null;
            });
        } catch (LockTimeoutException) {
            return response()->json([
                'message' => 'Занадто часті повідомлення. Зачекайте кілька секунд і спробуйте знову.',
                'code' => self::ERROR_CODE,
            ], 429);
        }
    }
}

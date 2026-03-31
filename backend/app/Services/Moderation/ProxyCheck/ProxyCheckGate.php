<?php

namespace App\Services\Moderation\ProxyCheck;

use App\Models\BanEvasionEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ProxyCheckGate
{
    public const ERROR_CODE = 'proxycheck_denied';

    public function __construct(
        private readonly ProxyCheckClient $client,
    ) {}

    public function denyIfNeeded(Request $request, string $tag): ?JsonResponse
    {
        $ip = (string) $request->ip();
        $verdict = $this->client->verdictForIp($ip, $tag);

        if (! $verdict->isDenied) {
            return null;
        }

        Log::notice('proxycheck denied request', [
            'ip' => $ip,
            'tag' => $tag,
            'reason' => $verdict->reason,
            'risk' => $verdict->riskScore,
        ]);

        try {
            BanEvasionEvent::query()->create([
                'user_id' => $request->user()?->id,
                'ip' => $ip,
                'action' => 'proxycheck_denied',
                'path' => $request->path(),
                'context' => [
                    'tag' => $tag,
                    'reason' => $verdict->reason,
                    'risk' => $verdict->riskScore,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('failed to persist proxycheck deny event', ['ip' => $ip, 'tag' => $tag, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Доступ тимчасово обмежено для цього підключення.',
            'code' => self::ERROR_CODE,
        ], 403);
    }
}


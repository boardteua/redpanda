<?php

namespace App\Http\Middleware;

use App\Models\BanEvasionEvent;
use App\Models\BannedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RejectBannedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();
        $key = BannedIp::cacheKeyFor($ip);

        $banned = Cache::remember($key, 120, function () use ($ip) {
            return BannedIp::query()->where('ip', $ip)->exists();
        });

        if ($banned) {
            try {
                BanEvasionEvent::query()->create([
                    'user_id' => $request->user()?->id,
                    'ip' => $ip,
                    'action' => 'banned_ip_request',
                    'path' => $request->path(),
                    'context' => [
                        'method' => $request->method(),
                        'route' => optional($request->route())->getName(),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('failed to persist ban_evasion_event', ['ip' => $ip, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'message' => 'Доступ з цієї IP-адреси заблоковано.',
            ], 403);
        }

        return $next($request);
    }
}

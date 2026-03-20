<?php

namespace App\Http\Middleware;

use App\Models\BannedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            return response()->json([
                'message' => 'Доступ з цієї IP-адреси заблоковано.',
            ], 403);
        }

        return $next($request);
    }
}

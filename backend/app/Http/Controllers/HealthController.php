<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Readiness: залежності, потрібні для обробки HTTP (БД; Redis — за конфігом).
     */
    public function ready(): JsonResponse
    {
        $checks = [];
        $ok = true;

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'fail';
            $ok = false;
        }

        if ($this->shouldCheckRedis()) {
            try {
                $pong = Redis::connection()->ping();
                $alive = $pong === true || $pong === '+PONG' || $pong === 'PONG';
                $checks['redis'] = $alive ? 'ok' : 'fail';
                if (! $alive) {
                    $ok = false;
                }
            } catch (\Throwable) {
                $checks['redis'] = 'fail';
                $ok = false;
            }
        }

        $payload = [
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
        ];

        return response()->json($payload, $ok ? 200 : 503);
    }

    private function shouldCheckRedis(): bool
    {
        if (filter_var(config('observability.health_check_redis'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $cache = (string) config('cache.default');
        $queue = (string) config('queue.default');

        return $cache === 'redis' || str_starts_with($cache, 'redis')
            || $queue === 'redis' || str_starts_with($queue, 'redis');
    }
}

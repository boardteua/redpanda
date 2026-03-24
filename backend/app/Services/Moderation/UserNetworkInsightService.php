<?php

namespace App\Services\Moderation;

use App\Models\BannedIp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class UserNetworkInsightService
{
    private const SESSION_SAMPLE_LIMIT = 50;

    private const UNIQUE_IP_CAP = 10;

    /**
     * @return array{
     *     user_id: int,
     *     latest_session: array{ip_address: string, last_activity_at: string, user_agent: string|null}|null,
     *     recent_ips: list<array{ip: string, last_seen_at: string, banned: bool}>,
     *     sessions_sampled: int
     * }
     */
    public function buildForUserId(int $userId): array
    {
        $rows = DB::table('sessions')
            ->where('user_id', $userId)
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->orderByDesc('last_activity')
            ->limit(self::SESSION_SAMPLE_LIMIT)
            ->get(['ip_address', 'user_agent', 'last_activity']);

        if ($rows->isEmpty()) {
            return [
                'user_id' => $userId,
                'latest_session' => null,
                'recent_ips' => [],
                'sessions_sampled' => 0,
            ];
        }

        $first = $rows->first();
        $latest = [
            'ip_address' => (string) $first->ip_address,
            'last_activity_at' => Carbon::createFromTimestamp((int) $first->last_activity)->toIso8601String(),
            'user_agent' => $first->user_agent !== null && $first->user_agent !== ''
                ? (string) $first->user_agent
                : null,
        ];

        $lastSeenByIp = [];
        foreach ($rows as $row) {
            $ip = (string) $row->ip_address;
            if ($ip === '') {
                continue;
            }
            $ts = (int) $row->last_activity;
            if (! isset($lastSeenByIp[$ip]) || $ts > $lastSeenByIp[$ip]) {
                $lastSeenByIp[$ip] = $ts;
            }
        }

        arsort($lastSeenByIp);
        $topIps = array_slice($lastSeenByIp, 0, self::UNIQUE_IP_CAP, true);
        $ipList = array_keys($topIps);
        $bannedSet = BannedIp::query()
            ->whereIn('ip', $ipList)
            ->pluck('ip')
            ->all();
        $bannedSet = array_fill_keys($bannedSet, true);
        $recentIps = [];
        foreach ($topIps as $ip => $lastActivity) {
            $recentIps[] = [
                'ip' => $ip,
                'last_seen_at' => Carbon::createFromTimestamp($lastActivity)->toIso8601String(),
                'banned' => isset($bannedSet[$ip]),
            ];
        }

        return [
            'user_id' => $userId,
            'latest_session' => $latest,
            'recent_ips' => $recentIps,
            'sessions_sampled' => $rows->count(),
        ];
    }
}

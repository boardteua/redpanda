<?php

namespace App\Services\Moderation\ProxyCheck;

use App\Models\ChatSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProxyCheckClient
{
    private const CACHE_PREFIX = 'proxycheck:v2:';

    public function verdictForIp(string $ip, string $tag): ProxyCheckVerdict
    {
        $ip = trim($ip);
        if ($ip === '') {
            return $this->allowVerdict($ip);
        }

        if (! $this->enabled()) {
            return $this->allowVerdict($ip);
        }

        $cacheKey = self::CACHE_PREFIX.hash('sha256', $ip);
        $ttl = max(10, min(86400, (int) config('services.proxycheck.cache_ttl_seconds', 600)));

        /** @var ProxyCheckVerdict $verdict */
        $verdict = Cache::remember($cacheKey, $ttl, function () use ($ip, $tag): ProxyCheckVerdict {
            return $this->fetchVerdict($ip, $tag);
        });

        return $verdict;
    }

    private function enabled(): bool
    {
        if (! (bool) config('services.proxycheck.enabled', false)) {
            return false;
        }

        try {
            return Cache::remember('chat_settings:proxycheck_enabled', 10, function (): bool {
                $v = ChatSetting::query()->value('proxycheck_enabled');

                return $v === null ? true : (bool) $v;
            });
        } catch (\Throwable $e) {
            Log::warning('proxycheck toggle lookup failed (fail-open)', ['error' => $e->getMessage()]);

            return true;
        }
    }

    private function key(): string
    {
        return (string) config('services.proxycheck.key', '');
    }

    private function timeoutSeconds(): int
    {
        $ms = (int) config('services.proxycheck.timeout_ms', 1500);

        return max(1, min(10, (int) ceil($ms / 1000)));
    }

    private function denyRiskThreshold(): int
    {
        return max(0, min(100, (int) config('services.proxycheck.deny_risk_threshold', 67)));
    }

    private function fetchVerdict(string $ip, string $tag): ProxyCheckVerdict
    {
        $key = $this->key();
        if ($key === '') {
            Log::warning('proxycheck disabled: missing key', ['ip' => $ip]);

            return $this->allowVerdict($ip);
        }

        try {
            $resp = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->get("https://proxycheck.io/v2/{$ip}", [
                    'key' => $key,
                    'vpn' => 1,
                    'risk' => 2,
                    'tag' => $tag,
                ])
                ->throw();
        } catch (ConnectionException $e) {
            Log::warning('proxycheck connection failed', ['ip' => $ip, 'tag' => $tag, 'error' => $e->getMessage()]);

            return $this->allowVerdict($ip);
        } catch (RequestException $e) {
            Log::warning('proxycheck request failed', [
                'ip' => $ip,
                'tag' => $tag,
                'status' => optional($e->response)->status(),
                'error' => $e->getMessage(),
            ]);

            return $this->allowVerdict($ip);
        }

        /** @var array<string, mixed> $json */
        $json = $resp->json();
        $status = strtolower((string) ($json['status'] ?? ''));
        if ($status !== '' && ! in_array($status, ['ok', 'warning'], true)) {
            Log::warning('proxycheck logical failure status', [
                'ip' => $ip,
                'tag' => $tag,
                'status' => $status,
                'message' => is_string($json['message'] ?? null) ? $json['message'] : null,
            ]);

            return $this->allowVerdict($ip);
        }

        $ipNode = $json[$ip] ?? null;
        if (! is_array($ipNode)) {
            Log::warning('proxycheck missing ip node in response', ['ip' => $ip, 'tag' => $tag, 'status' => $status]);

            return $this->allowVerdict($ip);
        }

        $proxyYes = (($ipNode['proxy'] ?? null) === 'yes');
        $type = (string) ($ipNode['type'] ?? '');
        $riskRaw = $ipNode['risk'] ?? null;
        $riskScore = null;
        if (is_int($riskRaw)) {
            $riskScore = $riskRaw;
        } elseif (is_string($riskRaw) && $riskRaw !== '' && ctype_digit($riskRaw)) {
            $riskScore = (int) $riskRaw;
        }

        $isProxyOrVpn = $proxyYes || strtoupper($type) === 'VPN';
        $deny = false;
        $reason = null;

        if ($isProxyOrVpn) {
            $deny = (bool) config('services.proxycheck.deny_if_proxy_or_vpn', true);
            $reason = 'proxy_or_vpn';
        }

        if (! $deny && $riskScore !== null && $riskScore >= $this->denyRiskThreshold()) {
            $deny = true;
            $reason = 'risk_score';
        }

        /** @var array<string, mixed>|null $raw */
        return new ProxyCheckVerdict(
            ip: $ip,
            isProxyOrVpn: $isProxyOrVpn,
            riskScore: $riskScore,
            isDenied: $deny,
            reason: $reason,
            raw: $ipNode,
        );
    }

    private function allowVerdict(string $ip): ProxyCheckVerdict
    {
        return new ProxyCheckVerdict(
            ip: $ip,
            isProxyOrVpn: false,
            riskScore: null,
            isDenied: false,
            reason: null,
            raw: null,
        );
    }
}


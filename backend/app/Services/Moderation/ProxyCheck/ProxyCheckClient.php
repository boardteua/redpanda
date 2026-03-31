<?php

namespace App\Services\Moderation\ProxyCheck;

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
            return new ProxyCheckVerdict(
                ip: $ip,
                isProxyOrVpn: false,
                riskScore: null,
                isDenied: false,
                reason: null,
                raw: null,
            );
        }

        if (! $this->enabled()) {
            return new ProxyCheckVerdict(
                ip: $ip,
                isProxyOrVpn: false,
                riskScore: null,
                isDenied: false,
                reason: null,
                raw: null,
            );
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
        return (bool) config('services.proxycheck.enabled', false);
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

            return new ProxyCheckVerdict(
                ip: $ip,
                isProxyOrVpn: false,
                riskScore: null,
                isDenied: false,
                reason: null,
                raw: null,
            );
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

            return new ProxyCheckVerdict(
                ip: $ip,
                isProxyOrVpn: false,
                riskScore: null,
                isDenied: false,
                reason: null,
                raw: null,
            );
        } catch (RequestException $e) {
            Log::warning('proxycheck request failed', [
                'ip' => $ip,
                'tag' => $tag,
                'status' => optional($e->response)->status(),
                'error' => $e->getMessage(),
            ]);

            return new ProxyCheckVerdict(
                ip: $ip,
                isProxyOrVpn: false,
                riskScore: null,
                isDenied: false,
                reason: null,
                raw: null,
            );
        }

        /** @var array<string, mixed> $json */
        $json = $resp->json();
        $ipNode = $json[$ip] ?? null;

        $proxyYes = is_array($ipNode) && (($ipNode['proxy'] ?? null) === 'yes');
        $type = is_array($ipNode) ? (string) ($ipNode['type'] ?? '') : '';
        $riskRaw = is_array($ipNode) ? ($ipNode['risk'] ?? null) : null;
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
        $raw = is_array($ipNode) ? $ipNode : null;

        return new ProxyCheckVerdict(
            ip: $ip,
            isProxyOrVpn: $isProxyOrVpn,
            riskScore: $riskScore,
            isDenied: $deny,
            reason: $reason,
            raw: $raw,
        );
    }
}


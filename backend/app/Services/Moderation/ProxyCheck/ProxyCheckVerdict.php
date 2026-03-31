<?php

namespace App\Services\Moderation\ProxyCheck;

final class ProxyCheckVerdict
{
    /**
     * @param  array<string, mixed>|null  $raw
     */
    public function __construct(
        public readonly string $ip,
        public readonly bool $isProxyOrVpn,
        public readonly ?int $riskScore,
        public readonly bool $isDenied,
        public readonly ?string $reason,
        public readonly ?array $raw,
    ) {}
}


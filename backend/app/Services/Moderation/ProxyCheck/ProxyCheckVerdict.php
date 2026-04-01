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

    /**
     * @return array{ip: string, isProxyOrVpn: bool, riskScore: ?int, isDenied: bool, reason: ?string, raw: ?array}
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'isProxyOrVpn' => $this->isProxyOrVpn,
            'riskScore' => $this->riskScore,
            'isDenied' => $this->isDenied,
            'reason' => $this->reason,
            'raw' => $this->raw,
        ];
    }

    /**
     * Відновлення з кешу (масив), без PHP-serialize об’єкта — уникнення __PHP_Incomplete_Class після деплоїв.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $riskRaw = $data['riskScore'] ?? null;
        $riskScore = null;
        if (is_int($riskRaw)) {
            $riskScore = $riskRaw;
        } elseif (is_string($riskRaw) && $riskRaw !== '' && ctype_digit($riskRaw)) {
            $riskScore = (int) $riskRaw;
        }

        $raw = $data['raw'] ?? null;

        return new self(
            ip: (string) ($data['ip'] ?? ''),
            isProxyOrVpn: (bool) ($data['isProxyOrVpn'] ?? false),
            riskScore: $riskScore,
            isDenied: (bool) ($data['isDenied'] ?? false),
            reason: isset($data['reason']) && is_string($data['reason']) ? $data['reason'] : null,
            raw: is_array($raw) ? $raw : null,
        );
    }
}


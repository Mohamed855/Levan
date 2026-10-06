<?php

declare(strict_types=1);

namespace App\Support\Security;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class TenantUpgradeRequestSignature
{
    public const SIGNATURE_HEADER = 'X-Upgrade-Signature';
    public const TIMESTAMP_HEADER = 'X-Upgrade-Timestamp';
    public const REQUEST_ID_HEADER = 'X-Upgrade-Request-Id';

    public static function make(string $tenantId, int $timestamp, string $requestId, string $secret): string
    {
        return hash_hmac('sha256', self::payload($tenantId, $timestamp, $requestId), $secret);
    }

    public static function payload(string $tenantId, int $timestamp, string $requestId): string
    {
        return implode("\n", [
            'tenant-upgrade-v1',
            $tenantId,
            (string) $timestamp,
            $requestId,
        ]);
    }

    public static function legacyCompatibilityIsEnabled(?DateTimeInterface $now = null): bool
    {
        $until = config('app.upgrade_legacy_secret_until');

        if (! is_string($until) || trim($until) === '') {
            return false;
        }

        try {
            return CarbonImmutable::instance($now ?? now())->lessThanOrEqualTo(CarbonImmutable::parse($until));
        } catch (\Throwable) {
            return false;
        }
    }
}

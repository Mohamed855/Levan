<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class TenantPasswordResetContext
{
    public static function matchesCurrentTenant(mixed $verification): bool
    {
        $tenantId = tenant('id');

        return is_array($verification)
            && $tenantId !== null
            && isset($verification['tenant_id'])
            && (string) $verification['tenant_id'] === (string) $tenantId;
    }
}

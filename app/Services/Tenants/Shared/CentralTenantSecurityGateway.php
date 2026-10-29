<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\Tenants\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralTenantSecurityGateway
{
    public function isTwoFactorRequired(?string $tenantId = null): bool
    {
        $targetTenantId = $tenantId ?? (string) tenant('id');

        if (! $targetTenantId) {
            return false;
        }

        return (bool) Tenancy::central(function () use ($targetTenantId): bool {
            return (bool) (Tenant::query()->find($targetTenantId)?->companySettings?->two_factor_enabled ?? false);
        });
    }
}

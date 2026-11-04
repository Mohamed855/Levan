<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\Tenants\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralTenantSecurityGateway
{
    public function forgetSubscriptionSnapshot(string $tenantId): void
    {
        cache()->forget("tenant:{$tenantId}:subscription");
    }

    public function isTwoFactorRequired(?string $tenantId = null): bool
    {
        return false;
    }
}


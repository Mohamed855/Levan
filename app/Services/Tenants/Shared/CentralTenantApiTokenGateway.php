<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralTenantApiTokenGateway
{
    public function regenerateCurrentTenantToken(): string
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($tenantId): string {
            $tenant = Tenant::query()->find($tenantId);

            if ($tenant === null) {
                throw (new ModelNotFoundException)->setModel(Tenant::class, [$tenantId]);
            }

            return $tenant->regenerateApiToken();
        });
    }
}

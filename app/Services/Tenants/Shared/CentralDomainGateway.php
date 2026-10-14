<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\Cpanel\CpanelAccount;
use App\Models\Admin\Tenants\Domain;
use Illuminate\Database\Eloquent\Collection;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralDomainGateway
{
    public function find(string $domain): ?Domain
    {
        return Tenancy::central(fn (): ?Domain => Domain::query()->where('domain', $domain)->first());
    }

    public function findForCurrentTenant(string $domain): ?Domain
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(fn (): ?Domain => Domain::query()
            ->where('tenant_id', $tenantId)
            ->where('domain', $domain)
            ->first());
    }

    public function findTransferred(string $domain): ?Domain
    {
        return Tenancy::central(fn (): ?Domain => Domain::query()
            ->where('domain', $domain)
            ->where('type', 'transfered')
            ->first());
    }

    public function createTransferred(string $domain, string $tenantId, string $orderId): Domain
    {
        return Tenancy::central(fn (): Domain => Domain::query()->create([
            'domain' => $domain,
            'tenant_id' => $tenantId,
            'is_active' => true,
            'type' => 'transfered',
            'transfer_code' => $orderId,
        ]));
    }

    public function firstForTenant(string $tenantId): ?Domain
    {
        return Tenancy::central(fn (): ?Domain => Domain::query()->where('tenant_id', $tenantId)->first());
    }

    /** @return Collection<int, Domain> */
    public function pendingTransfers(): Collection
    {
        return Tenancy::central(fn (): Collection => Domain::query()->where('trasnfer_status', 'pending')->get());
    }

    public function updateTransferStatus(Domain $domain, string $status): void
    {
        Tenancy::central(function () use ($domain, $status): void {
            $domain->update(['trasnfer_status' => $status]);
            if ($status === 'Completed') {
                $domain->update(['trasnfer_status' => null, 'transfer_code' => null]);
            }
        });
    }

    public function cpanelForTenant(string $tenantId): ?CpanelAccount
    {
        return Tenancy::central(fn (): ?CpanelAccount => CpanelAccount::query()->where('tenant_id', $tenantId)->first());
    }

    public function createCpanel(array $attributes): CpanelAccount
    {
        return Tenancy::central(fn (): CpanelAccount => CpanelAccount::query()->create($attributes));
    }
}

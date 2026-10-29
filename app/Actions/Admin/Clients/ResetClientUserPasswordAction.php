<?php

declare(strict_types=1);

namespace App\Actions\Admin\Clients;

use App\Actions\Tenants\Auth\ResetTenantPasswordAction;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\Gate;

final class ResetClientUserPasswordAction
{
    public function __construct(private readonly ResetTenantPasswordAction $passwords) {}

    public function execute(AdminUser $actor, string $tenantId, string $email, string $password): bool
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        Gate::forUser($actor)->authorize('update', $tenant);

        return $this->passwords->executeForTenant((string) $tenant->getKey(), $email, $password) !== null;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

final class TwoFactorClosureTenancyBootstrapper implements TenancyBootstrapper
{
    public static array $databases = [];

    public function bootstrap(Tenant $tenant): void
    {
        config(['database.connections.tenant.database' => self::$databases[$tenant->getTenantKey()]]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
    }

    public function revert(): void
    {
        DB::purge('tenant');
        DB::setDefaultConnection('mysql');
    }
}

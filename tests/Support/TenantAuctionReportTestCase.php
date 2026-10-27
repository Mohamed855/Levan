<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

abstract class TenantAuctionReportTestCase extends TestCase
{
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // This middleware switches to central and reconnects the tenant DB,
        // discarding uncommitted report fixtures before HTTP assertions run.
        $this->withoutMiddleware(EnsureTenantTwoFactorIsEnforced::class);

        DB::connection('mysql')->beginTransaction();

        $central = config('database.connections.mysql');
        $this->tenant = Tenant::query()->firstOrCreate(
            ['id' => 'test'],
            [
                'folderName' => 'test',
                'db_database' => 'levan_test',
                'db_username' => (string) ($central['username'] ?? 'root'),
                'db_password' => (string) ($central['password'] ?? ''),
                'db_host' => (string) ($central['host'] ?? '127.0.0.1'),
                'db_port' => (string) ($central['port'] ?? '3306'),
                'company_name' => 'شركة الاختبارات',
                'commercial_number' => '2222222222',
                'tax_number' => '300000000000003',
                'access_type' => 'path',
            ],
        );

        $this->tenant->domains()->firstOrCreate(['domain' => 'test.localhost']);

        tenancy()->initialize($this->tenant);
        DB::connection('tenant')->beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if (DB::connection('tenant')->transactionLevel() > 0) {
                DB::connection('tenant')->rollBack();
            }
        } catch (Throwable) {
            // The tenant connection may already have been reverted by the test.
        }

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }

        parent::tearDown();
    }
}

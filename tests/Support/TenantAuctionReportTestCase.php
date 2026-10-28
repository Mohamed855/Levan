<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\DB;
use App\Models\Tenants\UserMangment\User\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Throwable;

abstract class TenantAuctionReportTestCase extends TestCase
{
    protected Tenant $tenant;

    protected function setUp(): void
    {
        $settings = [
            'central' => (string) ($_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: ''),
            'tenant' => (string) ($_ENV['AUCTION_REPORT_TEST_TENANT_DATABASE'] ?? $_SERVER['AUCTION_REPORT_TEST_TENANT_DATABASE'] ?? getenv('AUCTION_REPORT_TEST_TENANT_DATABASE') ?: ''),
            'tenant_id' => (string) ($_ENV['AUCTION_REPORT_TEST_TENANT_ID'] ?? $_SERVER['AUCTION_REPORT_TEST_TENANT_ID'] ?? getenv('AUCTION_REPORT_TEST_TENANT_ID') ?: ''),
            'url' => (string) ($_ENV['DB_URL'] ?? $_SERVER['DB_URL'] ?? getenv('DB_URL') ?: ''),
        ];
        AuctionReportDatabaseGuard::assertSafe($settings);

        parent::setUp();
        AuctionReportDatabaseGuard::assertSafe(array_replace($settings, [
            'central' => (string) config('database.connections.mysql.database'),
            'url' => (string) config('database.connections.mysql.url'),
        ]));

        // This middleware switches to central and reconnects the tenant DB,
        // discarding uncommitted report fixtures before HTTP assertions run.
        $this->withoutMiddleware(EnsureTenantTwoFactorIsEnforced::class);

        DB::connection('mysql')->beginTransaction();

        $central = config('database.connections.mysql');
        $this->tenant = Tenant::query()->firstOrCreate(
            ['id' => $settings['tenant_id']],
            [
                'folderName' => $settings['tenant_id'],
                'db_database' => $settings['tenant'],
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

        AuctionReportDatabaseGuard::assertSafe(array_replace($settings, [
            'tenant' => (string) $this->tenant->db_database,
        ]));

        tenancy()->initialize($this->tenant);
        DB::connection('tenant')->beginTransaction();

        $owner = User::query()->firstOrCreate(['email' => 'demo@levan.com'], [
            'name' => 'Report CI owner', 'password' => bcrypt('Report-CI-only-123!'), 'status' => 'active',
        ]);
        foreach (['auctions.view', 'auctions.create', 'auctions.edit', 'auctions.delete'] as $name) {
            $permission = Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'tenant']);
            $owner->givePermissionTo($permission);
        }
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

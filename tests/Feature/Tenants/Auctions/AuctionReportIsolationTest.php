<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportIsolationTest extends TenantAuctionReportTestCase
{
    public function test_report_download_and_retry_routes_are_tenant_scoped(): void
    {
        self::assertTrue(Route::has('path.auctions.reports.download'));
        self::assertTrue(Route::has('domain.auctions.reports.download'));
        self::assertTrue(Route::has('path.auctions.reports.retry'));
        self::assertTrue(Route::has('domain.auctions.reports.retry'));
    }

    public function test_private_report_disk_is_rebound_when_tenant_context_changes(): void
    {
        $path = 'auction-reports/1/shared-revision/report.pdf';
        Storage::disk('tenant_private')->put($path, '%PDF-tenant-one');
        Storage::disk('tenant_private')->assertExists($path);

        $central = config('database.connections.mysql');
        $otherTenant = Tenant::query()->create([
            'id' => 'report-isolation-'.uniqid(),
            'folderName' => 'report-isolation-'.uniqid(),
            'db_database' => 'levan_test',
            'db_username' => (string) ($central['username'] ?? 'root'),
            'db_password' => (string) ($central['password'] ?? ''),
            'db_host' => (string) ($central['host'] ?? '127.0.0.1'),
            'db_port' => (string) ($central['port'] ?? '3306'),
            'company_name' => 'شركة اختبار عزل التقارير',
            'commercial_number' => (string) random_int(1000000000, 9999999999),
            'tax_number' => '3'.str_pad((string) random_int(1, 9999999999999), 13, '0', STR_PAD_LEFT).'3',
            'access_type' => 'path',
        ]);

        DB::connection('tenant')->rollBack();
        tenancy()->end();

        try {
            tenancy()->initialize($otherTenant);

            self::assertStringContainsString(
                'tenants-private/'.$otherTenant->id,
                str_replace('\\', '/', config('filesystems.disks.tenant_private.root')),
            );
            $privateRoot = str_replace('\\', '/', config('filesystems.disks.tenant_private.root'));
            foreach (config('filesystems.links') as $publicTarget) {
                self::assertFalse(str_starts_with($privateRoot.'/', rtrim(str_replace('\\', '/', $publicTarget), '/').'/'));
            }
            Storage::disk('tenant_private')->assertMissing($path);
        } finally {
            tenancy()->end();
            tenancy()->initialize($this->tenant);
            Storage::disk('tenant_private')->delete($path);
            DB::connection('tenant')->beginTransaction();
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportIsolationTest extends TenantAuctionReportTestCase
{
    public function test_authorized_user_downloads_both_private_pdfs_through_the_tenant_endpoint(): void
    {
        $this->withoutMiddleware([
            CheckSubscription::class,
            EnsureUserHasActiveLicense::class,
            EnsureTenantTwoFactorIsEnforced::class,
        ]);
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        $assignment = Assignment::query()->create(['assignment_number' => 'DOWNLOAD-'.uniqid(), 'name' => 'إسناد تنزيل', 'issuing_entity' => 'جهة اختبار', 'is_active' => true]);
        $auction = Auction::query()->create([
            'assignment_id' => $assignment->id, 'name' => 'مزاد تنزيل خاص', 'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(), 'type' => 'electronic', 'category' => 'lands',
            'city' => 'الرياض', 'is_active' => false,
        ]);
        $auction->reportGenerations()->create([
            'revision_token' => 'download-'.uniqid(), 'status' => 'ready', 'snapshot' => [],
            'outputs_path' => 'auction-reports/download/outputs.pdf', 'report_path' => 'auction-reports/download/report.pdf',
        ]);
        $owner = User::query()->where('email', 'demo@levan.com')->firstOrFail();
        self::assertTrue($auction->fresh()->latestReportGeneration->isReady());
        foreach (['outputs' => 'مخرجات', 'report' => 'تقرير'] as $type => $label) {
            Storage::disk('tenant_private')->put('auction-reports/download/'.$type.'.pdf', '%PDF-'.$type);
            $response = $this->actingAs($owner, 'tenant')->get('http://test.localhost/auctions/'.$auction->id.'/reports/download/'.$type);
            $response->assertOk()->assertDownload();
            self::assertStringContainsString(rawurlencode($label.'-مزاد-'.$auction->id.'.pdf'), $response->headers->get('Content-Disposition'));
            self::assertSame('%PDF-'.$type, $response->streamedContent());
            Storage::disk('tenant_public')->assertMissing('auction-reports/download/'.$type.'.pdf');
        }
        $viewer = User::query()->create([
            'name' => 'دون صلاحية تقارير', 'email' => 'no-report-'.uniqid().'@levan.test',
            'password' => bcrypt('Password123!'), 'status' => 'active',
        ]);
        $this->actingAs($viewer, 'tenant')->get('http://test.localhost/auctions/'.$auction->id.'/reports/download/outputs')->assertForbidden();
    }

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
            'db_database' => 'levan_report_ci_tenant',
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

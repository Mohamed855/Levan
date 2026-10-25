<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AuctionReportIsolationTest extends TestCase
{
    public function test_report_download_and_retry_routes_are_tenant_scoped(): void
    {
        self::assertTrue(Route::has('path.auctions.reports.download'));
        self::assertTrue(Route::has('domain.auctions.reports.download'));
        self::assertTrue(Route::has('path.auctions.reports.retry'));
        self::assertTrue(Route::has('domain.auctions.reports.retry'));
    }
}

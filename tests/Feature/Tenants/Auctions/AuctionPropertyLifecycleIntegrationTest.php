<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Services\Tenants\Auctions\AuctionService;
use Illuminate\Support\Facades\Queue;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionPropertyLifecycleIntegrationTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_manual_status_toggle_is_rejected_without_refreshing_reports(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        try {
            app(AuctionService::class)->toggleStatus($auction, false);
            self::fail('Manual auction status accepted.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertTrue($auction->fresh()->is_active);
        }
        self::assertSame('pending', $auction->fresh()->properties()->first()->pivot->result_status);
        self::assertNull($auction->latestReportGeneration()->first());
    }
}

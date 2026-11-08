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

    public function test_toggle_status_updates_is_active_and_refreshes_reports(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        self::assertFalse(app(AuctionService::class)->toggleStatus($auction, false));
        self::assertFalse($auction->fresh()->is_active);
        self::assertSame('pending', $auction->fresh()->properties()->first()->pivot->result_status);
        self::assertSame(false, $auction->latestReportGeneration()->first()->snapshot['auction']['is_active']);
    }
}

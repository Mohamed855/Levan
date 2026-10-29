<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;
use Tests\TestCase;

class AuctionPropertyLifecycleIntegrationTest extends TestCase
{
    public function test_toggle_status_only_updates_is_active(): void
    {
        $service = new AuctionService();

        $auction = new Auction([
            'is_active' => true,
        ]);

        // Mock update method
        $mockAuction = $this->getMockBuilder(Auction::class)
            ->onlyMethods(['update'])
            ->getMock();

        $mockAuction->is_active = true;

        $mockAuction->expects($this->once())
            ->method('update')
            ->with(['is_active' => false])
            ->willReturn(true);

        $result = $service->toggleStatus($mockAuction, false);
        $this->assertFalse($result);
    }
}



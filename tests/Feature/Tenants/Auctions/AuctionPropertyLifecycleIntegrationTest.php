<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;
use Tests\TestCase;

class AuctionPropertyLifecycleIntegrationTest extends TestCase
{
    public function test_toggle_status_only_updates_is_active_and_does_not_corrupt_status(): void
    {
        $service = new AuctionService();

        $auction = new Auction([
            'is_active' => true,
        ]);
        $auction->status = AuctionStatus::COMPLETED;

        // Mock update method
        $mockAuction = $this->getMockBuilder(Auction::class)
            ->onlyMethods(['update'])
            ->getMock();

        $mockAuction->is_active = true;
        $mockAuction->status = AuctionStatus::COMPLETED;

        $mockAuction->expects($this->once())
            ->method('update')
            ->with(['is_active' => false])
            ->willReturn(true);

        $result = $service->toggleStatus($mockAuction, false);
        $this->assertFalse($result);
    }
}


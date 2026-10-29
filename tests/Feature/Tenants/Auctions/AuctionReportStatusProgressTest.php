<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Tests\TestCase;

class AuctionReportStatusProgressTest extends TestCase
{
    public function test_auction_report_generation_stores_progress(): void
    {
        $generation = new AuctionReportGeneration([
            'progress' => 60,
            'status' => 'processing',
        ]);

        $this->assertSame(60, $generation->progress);
        $this->assertTrue($generation->isProcessing());
        $this->assertFalse($generation->isReady());
    }

    public function test_retry_request_authorization_rules(): void
    {
        $request = new \App\Http\Requests\Tenants\Auctions\RetryAuctionReportsRequest();
        $this->assertSame([], $request->rules());
    }
}


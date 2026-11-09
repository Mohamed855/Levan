<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\Enums\Tenants\Auctions\AuctionReportContentStatus;
use App\Models\Tenants\Auctions\Auction;
use Tests\TestCase;

final class AuctionCompletionPresentationTest extends TestCase
{
    public function test_marketing_completeness_is_a_separate_read_only_auction_attribute(): void
    {
        $auction = new Auction;
        $auction->setRelation('marketingCampaigns', collect());
        $auction->setRelation('properties', new \Illuminate\Database\Eloquent\Collection);
        $auction->setAttribute('completion_status', 'complete');
        self::assertSame(AuctionReportContentStatus::INCOMPLETE, $auction->completion_status);
    }

    public function test_auction_list_exposes_both_lifecycle_and_completeness(): void
    {
        $method = new \ReflectionMethod(AuctionsDataTable::class, 'getColumns');
        $columns = $method->invoke(new AuctionsDataTable);
        self::assertContains('status_badge', array_column($columns, 'data'));
        self::assertContains('completion_status', array_column($columns, 'data'));
    }
}

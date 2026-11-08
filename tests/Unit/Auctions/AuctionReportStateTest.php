<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportState;
use PHPUnit\Framework\TestCase;

final class AuctionReportStateTest extends TestCase
{
    public function test_campaigns_are_optional_by_type_but_required_for_content_completion(): void
    {
        $state = new AuctionReportState;
        self::assertSame('incomplete', $state->fromData([], false, ['pending'])->value);
        self::assertSame('complete', $state->fromData([['platform' => 'x', 'views' => 0]], false, ['pending'])->value);
    }

    public function test_only_saved_nonempty_all_sold_closing_is_ended(): void
    {
        $state = new AuctionReportState;
        self::assertSame('ended', $state->fromData([], true, ['sold', 'sold'])->value);
        self::assertSame('incomplete', $state->fromData([], true, [])->value);
        self::assertSame('complete', $state->fromData([['platform' => 'x', 'views' => 2]], true, ['sold', 'unsold'])->value);
        self::assertSame('incomplete', $state->fromData([], false, ['sold'])->value);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use PHPUnit\Framework\TestCase;
use Tests\Support\AuctionReportAcceptanceReceipt;

final class AuctionReportAcceptanceReceiptTest extends TestCase
{
    public function test_collection_always_contains_both_types_for_both_auction_revisions(): void
    {
        $items = (new AuctionReportAcceptanceReceipt)->documents(['auctions' => [
            ['id' => 1, 'generation' => 5, 'revision' => 'first', 'snapshot_sha256' => str_repeat('a', 64)],
            ['id' => 2, 'generation' => 6, 'revision' => 'second', 'snapshot_sha256' => str_repeat('b', 64)],
        ]]);
        self::assertCount(4, $items);
        self::assertSame([[1, 'outputs', 'first'], [1, 'report', 'first'], [2, 'outputs', 'second'], [2, 'report', 'second']], array_map(static fn ($item) => [$item['id'], $item['type'], $item['revision']], $items));
    }

    public function test_a_receipt_without_snapshot_identity_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AuctionReportAcceptanceReceipt)->documents(['auctions' => [['id' => 1, 'generation' => 5, 'revision' => 'first']]]);
    }
}

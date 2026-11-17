<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use Tests\TestCase;

final class HaramMarketingFlowTest extends TestCase
{
    public function test_tiktok_evidence_only_campaign_is_not_discarded(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/haram-native-index.php';
        $snapshot['marketing']['campaigns'] = [['id' => 2, 'platform' => 'tiktok']];
        $snapshot['evidence'] = ['social' => [['id' => 10, 'campaign_id' => 2, 'channel' => 'tiktok']]];
        $pages = (new AuctionReportPaginator)->paginate($snapshot, 'outputs');
        $marketing = array_values(array_filter($pages, static fn (array $page): bool => $page['page_type'] === 'haram-marketing'));
        self::assertCount(1, $marketing);
        self::assertSame([10], array_column($marketing[0]['payload']['tiktok'], 'id'));
    }

    public function test_more_campaign_images_do_not_repeat_other_platforms(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/haram-native-index.php';
        $snapshot['marketing']['campaigns'] = [
            ['id' => 1, 'platform' => 'x', 'views' => 50],
            ['id' => 2, 'platform' => 'tiktok', 'reach' => 55],
        ];
        $snapshot['evidence'] = ['x' => array_map(static fn (int $id): array => ['id' => $id, 'campaign_id' => 1, 'channel' => 'x'], range(1, 5))];
        $pages = (new AuctionReportPaginator)->paginate($snapshot, 'outputs');
        $marketing = array_values(array_filter($pages, static fn (array $page): bool => $page['page_type'] === 'haram-marketing'));
        self::assertCount(1, $marketing);
        self::assertSame(range(1, 5), array_column($marketing[0]['payload']['x'], 'id'));
        self::assertSame([1, 2], array_column($marketing[0]['payload']['campaigns'], 'id'));
    }
}

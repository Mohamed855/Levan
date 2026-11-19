<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use Tests\TestCase;

final class AuctionReportInputCoverageTest extends TestCase
{
    public function test_campaign_catalog_contains_only_seven_channels_and_excludes_moved_sections(): void
    {
        $catalog = config('auction-marketing-campaigns');
        self::assertSame(['youtube', 'x', 'snapchat', 'tiktok', 'whatsapp', 'google', 'marketing_evidence'], array_keys($catalog));
        self::assertSame(['views'], $catalog['google']);
        self::assertSame(['views'], $catalog['snapchat']);
        self::assertSame(['views', 'posts', 'x_context'], $catalog['x']);
        self::assertNotContains('total', $catalog['whatsapp']);
        self::assertNotContains('impressions', $catalog['x']);
        self::assertNotContains('impressions', $catalog['snapchat']);
        foreach (['planning', 'promotion', 'brochure', 'platform', 'board_before', 'brokers', 'other', 'summary', 'design'] as $removed) {
            self::assertArrayNotHasKey($removed, $catalog);
        }
    }

    public function test_auction_marketing_data_renders_planning_city_context_design_and_promotion_pages(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing'] = [
            'planning_methodology' => 'منهجية التخطيط',
            'city_context' => 'منطقة الرياض، منطقة مكة المكرمة',
            'brochure_methodology' => 'تفاصيل الكتيب الأول',
            'audience_methodology' => 'الفئة المستهدفة',
            'campaigns' => [],
        ];
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $planning = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-planning'));
        self::assertCount(1, $planning);
        $binder = new AsirReportData(new AuctionReportFormat);
        $planningContent = $binder->page($snapshot, $planning[0]);
        $roles = array_column($planningContent['paragraphs'], 'role');
        self::assertContains('planning_methodology', $roles);
        self::assertContains('city_context', $roles);
        self::assertCount(1, array_filter($pages, fn ($page) => $page['page_type'] === 'asir-design'));
        self::assertCount(1, array_filter($pages, fn ($page) => $page['page_type'] === 'asir-promotion'));
    }

    public function test_unified_social_context_is_omitted_without_social_channels_and_printed_once_with_them(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;

        // Without any social platform added, social_context must NOT create an asir-social page
        $snapshot['marketing'] = ['social_context' => 'تفاصيل موحدة', 'campaigns' => [['id' => 1, 'platform' => 'google', 'views' => 50]]];
        $pagesWithoutSocial = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        self::assertCount(0, array_filter($pagesWithoutSocial, fn ($page) => $page['page_type'] === 'asir-social'));

        // With multiple social channels, they stay grouped on ONE asir-social page with unified social_context
        $snapshot['marketing'] = [
            'social_context' => 'تفاصيل حملات التواصل الموحدة',
            'campaigns' => [
                ['id' => 1, 'platform' => 'snapchat', 'views' => 100, 'url' => 'https://example.com/snap'],
                ['id' => 2, 'platform' => 'tiktok', 'views' => 200, 'url' => 'https://example.com/tiktok'],
                ['id' => 3, 'platform' => 'x', 'views' => 300, 'url' => 'https://example.com/x'],
            ],
        ];
        $socialPages = array_values(array_filter(app(AuctionReportPaginator::class)->paginate($snapshot, 'report'), fn ($page) => $page['page_type'] === 'asir-social'));
        self::assertCount(1, $socialPages);
        $binder = new AsirReportData(new AuctionReportFormat);
        self::assertSame('تفاصيل حملات التواصل الموحدة', $binder->page($snapshot, $socialPages[0])['values']['social_context']);
    }

    public function test_auction_brokers_and_contract_text_render_on_asir_brokers_page(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing'] = [
            'contract_text' => 'نص العقد',
            'brokers' => [
                ['key' => 'b-1', 'name' => 'الوسيط الأول'],
                ['key' => 'b-2', 'name' => 'الوسيط الثاني'],
            ],
            'campaigns' => [],
        ];
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $page = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-brokers'))[0];
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, $page);
        self::assertSame('الوسيط الأول', $content['values']['broker_1']);
        self::assertSame('الوسيط الثاني', $content['values']['broker_2']);
        self::assertSame('نص العقد', $content['values']['contract_text']);
    }

    public function test_google_statistics_do_not_create_board_pages_or_duplicate_removal_evidence(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['closing_saved'] = false;
        $snapshot['marketing'] = ['campaigns' => [['id' => 1, 'platform' => 'google', 'views' => 0], ['id' => 2, 'platform' => 'google', 'views' => 20]]];
        $snapshot['evidence']['board_after'] = [['id' => 9, 'uri' => null]];
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        self::assertNotContains('asir-board-removal-1', array_column($pages, 'page_type'));
        self::assertNotContains('asir-summary', array_column($pages, 'page_type'));

        $snapshot['closing_saved'] = true;
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $removal = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-board-removal-1'));
        self::assertCount(1, $removal);
        self::assertSame([9], array_column($removal[0]['payload']['board_after'], 'id'));
        self::assertArrayNotHasKey('campaigns', $removal[0]['payload']);
    }
}

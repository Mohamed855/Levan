<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use Tests\TestCase;

final class AuctionReportInputCoverageTest extends TestCase
{
    public function test_campaign_catalog_contains_native_sections_and_excludes_unplaced_metrics(): void
    {
        $catalog = config('auction-marketing-campaigns');
        self::assertSame(['views'], $catalog['google']);
        self::assertSame(['planning_methodology', 'city_context', 'brochure_methodology'], $catalog['planning']);
        self::assertArrayNotHasKey('other', $catalog);
        self::assertArrayNotHasKey('summary', $catalog);
        self::assertArrayNotHasKey('design', $catalog);
        self::assertNotContains('clicks', $catalog['x']);
        foreach (['planning', 'promotion', 'brochure', 'platform', 'board_before', 'brokers'] as $type) {
            self::assertArrayHasKey($type, $catalog);
        }
    }

    public function test_partial_text_sections_repeat_original_pages_with_their_own_data(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing'] = ['campaigns' => [
            ['id' => 1, 'platform' => 'planning', 'brochure_methodology' => 'تفاصيل الكتيب الأول'],
            ['id' => 2, 'platform' => 'planning', 'brochure_methodology' => 'تفاصيل الكتيب الثاني'],
            ['id' => 3, 'platform' => 'promotion', 'audience_methodology' => 'الفئة المستهدفة'],
        ]];
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $design = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-design'));
        self::assertCount(2, $design);
        $binder = new AsirReportData(new AuctionReportFormat);
        $first = $binder->page($snapshot, $design[0]);
        $second = $binder->page($snapshot, $design[1]);
        self::assertSame('اختيار اسم المزاد وإعداد الهوية البصرية - '.$snapshot['auction']['name'], $first['values']['name_context']);
        self::assertSame('تفاصيل الكتيب الأول', $first['values']['brochure_methodology']);
        self::assertSame('اختيار اسم المزاد وإعداد الهوية البصرية - '.$snapshot['auction']['name'], $second['values']['name_context']);
        self::assertSame('تفاصيل الكتيب الثاني', $second['values']['brochure_methodology']);
        self::assertCount(1, array_filter($pages, fn ($page) => $page['page_type'] === 'asir-promotion'));
    }

    public function test_social_text_or_url_alone_displays_the_original_social_page(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        foreach ([['platform' => 'snapchat', 'social_context' => 'تفاصيل الحملة'], ['platform' => 'snapchat', 'url' => 'https://example.com/snap']] as $campaign) {
            $snapshot['marketing'] = ['campaigns' => [$campaign]];
            $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
            self::assertCount(1, array_filter($pages, fn ($page) => $page['page_type'] === 'asir-social'));
        }
    }

    public function test_both_broker_name_slots_and_contract_text_are_enterable_without_files(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing'] = ['campaigns' => [['id' => 8, 'platform' => 'brokers', 'display_name' => 'الوسيط الأول', 'broker_second_name' => 'الوسيط الثاني', 'contract_text' => 'نص العقد']]];
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $page = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-brokers'))[0];
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, $page);
        self::assertSame('الوسيط الأول', $content['values']['broker_1']);
        self::assertSame('الوسيط الثاني', $content['values']['broker_2']);
        self::assertSame('نص العقد', $content['values']['contract_text']);
    }

    public function test_social_descriptions_keep_separate_native_field_capacity(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing'] = ['campaigns' => [['id' => 1, 'platform' => 'snapchat', 'social_context' => 'حملة سناب'], ['id' => 2, 'platform' => 'tiktok', 'social_context' => 'حملة تيك توك']]];
        $pages = array_values(array_filter(app(AuctionReportPaginator::class)->paginate($snapshot, 'report'), fn ($page) => $page['page_type'] === 'asir-social'));
        self::assertCount(2, $pages);
        $binder = new AsirReportData(new AuctionReportFormat);
        self::assertSame('حملة سناب', $binder->page($snapshot, $pages[0])['values']['social_context']);
        self::assertSame('حملة تيك توك', $binder->page($snapshot, $pages[1])['values']['social_context']);
    }

    public function test_google_views_use_the_existing_native_slot_without_early_removal_evidence(): void
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['closing_saved'] = false;
        $snapshot['marketing'] = ['campaigns' => [['id' => 1, 'platform' => 'google', 'views' => 0], ['id' => 2, 'platform' => 'google', 'views' => 20]]];
        $snapshot['evidence']['board_after'] = [['id' => 9, 'uri' => null]];
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $google = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-board-removal-1'));
        self::assertCount(2, $google);
        self::assertSame([], $google[0]['payload']['board_after']);
        $binder = new AsirReportData(new AuctionReportFormat);
        self::assertSame('0', $binder->page($snapshot, $google[0])['values']['google_views']);
        self::assertSame('مشاهدات عبر حملة جوجل', $binder->page($snapshot, $google[0])['values']['google_caption']);
        self::assertSame('20', $binder->page($snapshot, $google[1])['values']['google_views']);
        self::assertNotContains('asir-summary', array_column($pages, 'page_type'));
    }
}

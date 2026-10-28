<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use App\Services\Tenants\Auctions\Reports\HaramReportData;
use PHPUnit\Framework\TestCase;

final class HaramReportDataTest extends TestCase
{
    public function test_duplicate_channel_summaries_never_mix_an_aggregate_with_the_first_campaign(): void
    {
        $campaigns = [
            ['platform' => 'snapchat', 'impressions' => 10], ['platform' => 'سناب شات', 'impressions' => 20],
            ['platform' => 'tiktok', 'views' => 4], ['platform' => 'تيك توك', 'views' => 9],
        ];
        $snapshot = ['auction' => ['name' => 'اختبار'], 'properties' => [], 'marketing' => ['campaigns' => $campaigns]];
        $page = (new HaramReportData(new AuctionReportFormat))->page($snapshot, ['page_type' => 'haram-marketing', 'payload' => ['campaigns' => []]]);
        self::assertSame('30', $page['values']['snapchat_impressions']);
        self::assertSame('30', $page['values']['snapchat_views']);
        self::assertSame('13', $page['values']['tiktok_views']);
    }

    public function test_campaigns_are_bound_by_platform_and_preserve_a_recorded_zero(): void
    {
        $campaigns = [
            ['platform' => 'تيك توك', 'impressions' => 100],
            ['platform' => 'سناب شات', 'impressions' => 0],
            ['platform' => 'X', 'impressions' => 300],
        ];
        $snapshot = [
            'auction' => ['name' => 'مزاد اختبار', 'description' => '', 'start_date' => null],
            'properties' => [], 'marketing' => ['campaigns' => $campaigns],
        ];
        $page = (new HaramReportData(new AuctionReportFormat))->page($snapshot, ['page_type' => 'haram-marketing', 'payload' => ['campaigns' => $campaigns]]);

        self::assertSame('0', $page['values']['snapchat_impressions']);
        self::assertSame('100', $page['values']['tiktok_impressions']);
        self::assertSame('300', $page['values']['x_impressions']);
    }

    public function test_unknown_platforms_cannot_impersonate_another_native_channel(): void
    {
        $campaigns = [['platform' => 'Google', 'impressions' => 42], ['platform' => 'تيك توك', 'impressions' => 0]];
        $snapshot = ['auction' => ['name' => 'اختبار'], 'properties' => [], 'marketing' => ['campaigns' => $campaigns]];
        $page = (new HaramReportData(new AuctionReportFormat))->page($snapshot, ['page_type' => 'haram-marketing', 'payload' => ['campaigns' => $campaigns]]);
        self::assertSame('منصة سناب شات', $page['values']['snapchat_label']);
        self::assertSame('-', $page['values']['snapchat_impressions']);
        self::assertSame('منصة تيك توك', $page['values']['tiktok_label']);
        self::assertSame('0', $page['values']['tiktok_impressions']);
    }
}

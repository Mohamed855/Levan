<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use App\Services\Tenants\Auctions\Reports\HaramReportData;
use PHPUnit\Framework\TestCase;

final class HaramReportDataTest extends TestCase
{
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
}

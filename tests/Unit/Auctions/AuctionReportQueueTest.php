<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use Tests\TestCase;

final class AuctionReportQueueTest extends TestCase
{
    public function test_reports_use_a_central_durable_queue_with_a_safe_retry_window(): void
    {
        $connection = config('auction-renderer.queue_connection');
        self::assertSame('auction_reports', $connection);
        self::assertSame('database', config("queue.connections.$connection.driver"));
        self::assertSame(config('tenancy.database.central_connection'), config("queue.connections.$connection.connection"));
        self::assertTrue(config("queue.connections.$connection.central"));
        self::assertGreaterThan((new GenerateAuctionReportsJob('test', 1, 1, 'revision'))->timeout, config("queue.connections.$connection.retry_after"));
    }

    public function test_development_startup_includes_the_report_worker(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        self::assertStringContainsString('queue:work auction_reports', implode(' ', $composer['scripts']['dev']));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\QueryWatcher;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportSnapshotRecordingTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_large_report_snapshot_is_saved_without_expanding_it_in_telescope_queries(): void
    {
        Storage::fake('tenant_public');
        $bytes = (int) (getenv('AUCTION_REPORT_TEST_IMAGE_BYTES') ?: 1048576);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg==', true);
        Storage::disk('tenant_public')->put('snapshot-cover.png', $png.str_repeat("\0", $bytes));
        $auction = $this->cycleAuction();
        $auction->update(['cover_image' => 'snapshot-cover.png']);

        $recordedSnapshotQueries = [];
        DB::listen(static function (QueryExecuted $event) use (&$recordedSnapshotQueries): void {
            if (str_starts_with($event->sql, 'insert into `auction_report_generations`')) {
                $recordedSnapshotQueries[] = Telescope::isRecording();
            }
        });
        (new QueryWatcher)->register($this->app);
        $wasRecording = Telescope::$shouldRecord;
        Telescope::$shouldRecord = true;
        try {
            $generation = app(AuctionReportService::class)->requestGeneration($auction, (string) tenant('id'));
            self::assertNotNull($generation);
            self::assertSame('queued', $generation->status);
            self::assertGreaterThan($bytes, strlen($generation->snapshot['auction']['cover_image']));
            self::assertSame([false], $recordedSnapshotQueries, 'Telescope must not expand the binary report snapshot into SQL.');
            self::assertTrue(Telescope::isRecording(), 'Recording must resume after saving the snapshot.');
        } finally {
            Telescope::$shouldRecord = $wasRecording;
            Telescope::flushEntries();
        }
    }
}

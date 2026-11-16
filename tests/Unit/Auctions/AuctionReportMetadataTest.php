<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Http\Controllers\Tenants\Auctions\AuctionReportController;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AuctionReportMetadataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'report_metadata_test',
            'database.connections.report_metadata_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        ]);
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('published_report_generation_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('auction_report_generations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->string('revision_token');
            $table->string('status');
            $table->unsignedInteger('progress')->default(0);
            $table->text('snapshot');
            $table->string('outputs_path')->nullable();
            $table->string('report_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('report_metadata_test');
        parent::tearDown();
    }

    public function test_status_loads_only_metadata_and_preserves_the_published_pair_during_regeneration(): void
    {
        Gate::shouldReceive('authorize')->with('view', \Mockery::type(Auction::class))->andReturnTrue();
        $auction = Auction::create([]);
        $published = $auction->reportGenerations()->create([
            'revision_token' => 'published', 'status' => 'ready', 'progress' => 100,
            'outputs_path' => 'outputs.pdf', 'report_path' => 'report.pdf',
            'snapshot' => ['image' => str_repeat('x', 12 * 1024 * 1024)],
        ]);
        $auction->update(['published_report_generation_id' => $published->id]);
        $latest = $auction->reportGenerations()->create([
            'revision_token' => 'latest', 'status' => 'processing', 'progress' => 30,
            'snapshot' => ['image' => 'latest-image'],
        ]);
        $auction = $auction->fresh();
        $data = app(AuctionReportController::class)->status($auction)->getData(true);

        self::assertSame($latest->id, $data['generation_id']);
        self::assertSame('processing', $data['status']);
        self::assertSame(30, $data['progress']);
        self::assertTrue($data['has_outputs']);
        self::assertTrue($data['has_report']);
        self::assertArrayNotHasKey('snapshot', $auction->latestReportGeneration->getAttributes());
        self::assertArrayNotHasKey('snapshot', $auction->publishedReportGeneration->getAttributes());
        self::assertSame('latest-image', $latest->fresh()->snapshot['image']);
    }

    public function test_start_dispatches_only_once_without_loading_or_reloading_the_snapshot(): void
    {
        Queue::fake();
        Cache::shouldReceive('add')->andReturnFalse();
        $auction = Auction::create([]);
        $stored = $auction->reportGenerations()->create([
            'revision_token' => 'queued', 'status' => 'queued', 'snapshot' => ['image' => 'keep-image'],
        ]);
        $service = app(AuctionReportService::class);
        $started = $service->startPendingGeneration($auction, 'metadata-tenant');
        $again = $service->startPendingGeneration($auction, 'metadata-tenant');

        self::assertSame($stored->id, $started?->id);
        self::assertSame(5, $started?->progress);
        self::assertSame(5, $again?->progress);
        self::assertArrayNotHasKey('snapshot', $started->getAttributes());
        self::assertArrayNotHasKey('snapshot', $again->getAttributes());
        self::assertSame(['image' => 'keep-image'], $stored->fresh()->snapshot);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 1);
        Queue::assertPushedOn('auction-reports', GenerateAuctionReportsJob::class);
    }

    public function test_downloads_use_published_metadata_and_leave_the_snapshot_in_storage(): void
    {
        Gate::shouldReceive('authorize')->with('view', \Mockery::type(Auction::class))->andReturnTrue();
        Storage::fake('tenant_private');
        $auction = Auction::create([]);
        $published = $auction->reportGenerations()->create([
            'revision_token' => 'download', 'status' => 'ready', 'progress' => 100,
            'outputs_path' => 'outputs.pdf', 'report_path' => 'report.pdf',
            'snapshot' => ['image' => 'saved-image'],
        ]);
        $auction->update(['published_report_generation_id' => $published->id]);
        $controller = app(AuctionReportController::class);
        foreach (['outputs', 'report'] as $type) {
            Storage::disk('tenant_private')->put($type.'.pdf', '%PDF-'.$type);
            $freshAuction = $auction->fresh();
            $response = $controller->download($freshAuction, $type);
            self::assertSame(200, $response->getStatusCode());
            self::assertArrayNotHasKey('snapshot', $freshAuction->publishedReportGeneration->getAttributes());
            ob_start();
            $response->sendContent();
            self::assertSame('%PDF-'.$type, ob_get_clean());
        }
        self::assertSame(['image' => 'saved-image'], $published->fresh()->snapshot);
    }

    public function test_auction_without_a_generation_still_returns_none(): void
    {
        Gate::shouldReceive('authorize')->with('view', \Mockery::type(Auction::class))->andReturnTrue();
        self::assertSame(['status' => 'none'], app(AuctionReportController::class)->status(Auction::create([]))->getData(true));
    }
}

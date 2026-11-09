<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Jobs\Tenants\Auctions\GenerateAuctionRegulatoryFormJob;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionRegulatoryFormGenerationTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    private function generation(int $auctionId, string $type = 'establishment', array $snapshot = []): AuctionFormGeneration
    {
        return AuctionFormGeneration::create([
            'auction_id' => $auctionId, 'type' => $type, 'revision_token' => (string) Str::uuid(),
            'status' => 'queued', 'progress' => 0, 'snapshot' => $snapshot,
        ]);
    }

    private function renderer(bool $valid = true, ?\Closure $duringRender = null): BrowserDocumentRenderer
    {
        return new class($valid, $duringRender) implements BrowserDocumentRenderer
        {
            public function __construct(private bool $valid, private ?\Closure $duringRender) {}

            public function pdf(string $html, string $outputPath): string
            {
                if (! str_ends_with($outputPath, '.pdf')) {
                    throw new \RuntimeException('The native browser renderer requires a PDF file extension.');
                }
                ($this->duringRender ?? static fn () => null)();
                file_put_contents($outputPath, $this->valid ? "%PDF-1.4\nfixture\n%%EOF" : 'Invalid document');

                return $outputPath;
            }

            public function png(string $html, string $outputPath): string
            {
                throw new \LogicException('PDF generation must not request PNG output.');
            }
        };
    }

    public function test_generation_is_saved_without_dispatch_and_keeps_snapshot_after_request_changes(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $request = AuctionEstablishmentRequest::create([
            'auction_id' => $auction->id, 'submitted_at' => now(),
            'data' => ['auction' => ['name' => 'Original saved name']],
        ]);
        $service = app(AuctionRegulatoryFormService::class);
        $generation = $service->requestGeneration($auction, 'establishment');
        self::assertNotNull($generation);
        self::assertSame('queued', $generation->status);
        self::assertSame(0, $generation->progress);
        Queue::assertNothingPushed();
        $request->update(['data' => ['auction' => ['name' => 'Changed request']]]);
        self::assertSame(['auction' => ['name' => 'Original saved name']], $generation->fresh()->snapshot);
    }

    public function test_auction_without_saved_request_cannot_create_establishment_generation(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        self::assertNull(app(AuctionRegulatoryFormService::class)->requestGeneration($auction, 'establishment'));
        self::assertSame(0, AuctionFormGeneration::where('auction_id', $auction->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_generation_snapshot_cannot_be_rewritten(): void
    {
        $generation = $this->generation($this->cycleAuction()->id, snapshot: ['auction' => ['name' => 'Original']]);
        $this->expectException(\LogicException::class);
        $generation->update(['snapshot' => ['auction' => ['name' => 'Rewritten']]]);
    }

    public function test_deferred_start_dispatches_latest_type_once_and_uses_background_reports_queue(): void
    {
        self::assertTrue(class_exists(GenerateAuctionRegulatoryFormJob::class));
        Queue::fake();
        Cache::put('auction_reports_worker_spawn_lock', 1, 60);
        config(['auction-renderer.queue_connection' => 'sync']);
        $auction = $this->cycleAuction();
        $old = $this->generation($auction->id);
        $latest = $this->generation($auction->id);
        $closing = $this->generation($auction->id, 'closing');
        $service = app(AuctionRegulatoryFormService::class);
        $service->startPendingGeneration($auction, 'establishment', (string) tenant('id'));
        $service->startPendingGeneration($auction, 'establishment', (string) tenant('id'));
        Queue::assertPushed(GenerateAuctionRegulatoryFormJob::class, 1);
        Queue::assertPushedOn('auction-reports', GenerateAuctionRegulatoryFormJob::class);
        Queue::assertPushed(GenerateAuctionRegulatoryFormJob::class, fn ($job) => $job->connection === 'auction_reports');
        self::assertSame(0, $old->fresh()->progress);
        self::assertSame(5, $latest->fresh()->progress);
        self::assertSame(0, $closing->fresh()->progress);
    }

    public function test_job_renders_the_saved_snapshot_and_preserves_previous_ready_pdf(): void
    {
        self::assertTrue(class_exists(GenerateAuctionRegulatoryFormJob::class));
        Storage::fake('tenant_private');
        $auction = $this->cycleAuction();
        $previous = $this->generation($auction->id);
        $previous->update(['status' => 'ready', 'progress' => 100, 'file_path' => 'previous.pdf']);
        Storage::disk('tenant_private')->put('previous.pdf', 'previous');
        $current = $this->generation($auction->id, snapshot: ['auction' => ['name' => 'Saved original']]);
        $job = new GenerateAuctionRegulatoryFormJob((string) tenant('id'), $auction->id, $current->id, $current->revision_token);
        $job->handle(app(AuctionRegulatoryForms::class), $this->renderer());
        self::assertSame('ready', $current->fresh()->status);
        self::assertSame(100, $current->fresh()->progress);
        self::assertSame(['auction' => ['name' => 'Saved original']], $current->fresh()->snapshot);
        Storage::disk('tenant_private')->assertExists($current->fresh()->file_path);
        Storage::disk('tenant_private')->assertExists('previous.pdf');
        self::assertSame('ready', $previous->fresh()->status);
    }

    public function test_stale_revision_finishing_after_new_revision_cannot_publish(): void
    {
        self::assertTrue(class_exists(GenerateAuctionRegulatoryFormJob::class));
        Storage::fake('tenant_private');
        $auction = $this->cycleAuction();
        $current = $this->generation($auction->id);
        $job = new GenerateAuctionRegulatoryFormJob((string) tenant('id'), $auction->id, $current->id, $current->revision_token);
        $job->handle(app(AuctionRegulatoryForms::class), $this->renderer(duringRender: fn () => $this->generation($auction->id)));
        self::assertSame('superseded', $current->fresh()->status);
        self::assertNull($current->fresh()->file_path);
        self::assertSame([], Storage::disk('tenant_private')->allFiles());
    }

    public function test_already_stale_revision_does_not_invoke_renderer(): void
    {
        $auction = $this->cycleAuction();
        $old = $this->generation($auction->id);
        $this->generation($auction->id);
        (new GenerateAuctionRegulatoryFormJob((string) tenant('id'), $auction->id, $old->id, $old->revision_token))
            ->handle(app(AuctionRegulatoryForms::class), $this->renderer(duringRender: fn () => self::fail('A stale job must not render.')));
        self::assertSame('superseded', $old->fresh()->status);
    }

    public function test_invalid_tenant_does_not_leave_previous_tenant_uninitialized(): void
    {
        $previous = (string) tenant('id');
        try {
            (new GenerateAuctionRegulatoryFormJob('missing-regulatory-form-tenant', 1, 1, (string) Str::uuid()))
                ->handle(app(AuctionRegulatoryForms::class), $this->renderer());
            self::fail('Unknown tenants must be rejected.');
        } catch (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById) {
            self::assertTrue(tenancy()->initialized);
            self::assertSame($previous, (string) tenant('id'));
        }
    }

    public function test_invalid_renderer_output_fails_without_publishing_or_removing_last_good_file(): void
    {
        self::assertTrue(class_exists(GenerateAuctionRegulatoryFormJob::class));
        Storage::fake('tenant_private');
        $auction = $this->cycleAuction();
        $previous = $this->generation($auction->id);
        $previous->update(['status' => 'ready', 'file_path' => 'good.pdf']);
        Storage::disk('tenant_private')->put('good.pdf', 'good');
        $current = $this->generation($auction->id);
        try {
            (new GenerateAuctionRegulatoryFormJob((string) tenant('id'), $auction->id, $current->id, $current->revision_token))
                ->handle(app(AuctionRegulatoryForms::class), $this->renderer(false));
            self::fail('Invalid renderer output must fail.');
        } catch (\RuntimeException) {
            self::assertSame('failed', $current->fresh()->status);
            self::assertNull($current->fresh()->file_path);
            Storage::disk('tenant_private')->assertExists('good.pdf');
            self::assertSame('ready', $previous->fresh()->status);
        }
    }
}

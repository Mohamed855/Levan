<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportGenerationTest extends TenantAuctionReportTestCase
{
    public function test_job_stores_both_documents_on_private_disk_only(): void
    {
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        [$auction, $generation] = $this->generation('private-ready');
        $pdf = $this->successfulPdfRenderer();

        $this->job($auction, $generation)->handle(
            app(AuctionReportDataBuilder::class), app(AuctionReportRenderer::class), $pdf,
        );

        $generation->refresh();
        self::assertSame('ready', $generation->status);
        Storage::disk('tenant_private')->assertExists([$generation->outputs_path, $generation->report_path]);
        Storage::disk('tenant_public')->assertMissing([$generation->outputs_path, $generation->report_path]);
    }

    public function test_second_document_failure_cleans_temporary_and_private_files(): void
    {
        Storage::fake('tenant_private');
        [$auction, $generation] = $this->generation('second-fails');
        $pdf = new class implements BrowserDocumentRenderer {
            public int $calls = 0;
            public function pdf(string $html, string $outputPath): string
            {
                $this->calls++;
                if ($this->calls === 2) {
                    throw new RuntimeException('second document failed');
                }
                file_put_contents($outputPath, '%PDF-first');
                return $outputPath;
            }
            public function png(string $html, string $outputPath): string { return $outputPath; }
        };

        try {
            $this->job($auction, $generation)->handle(
                app(AuctionReportDataBuilder::class), app(AuctionReportRenderer::class), $pdf,
            );
            self::fail('The generation should fail on the second document.');
        } catch (RuntimeException $exception) {
            self::assertSame('second document failed', $exception->getMessage());
        }

        $generation->refresh();
        self::assertSame('failed', $generation->status);
        self::assertNull($generation->outputs_path);
        self::assertNull($generation->report_path);
        self::assertFalse(file_exists(sys_get_temp_dir().'/auction-outputs-'.$generation->revision_token.'.pdf'));
        self::assertFalse(file_exists(sys_get_temp_dir().'/auction-report-'.$generation->revision_token.'.pdf'));
        self::assertSame([], Storage::disk('tenant_private')->allFiles());
    }

    public function test_generation_that_becomes_stale_during_render_is_never_published(): void
    {
        Storage::fake('tenant_private');
        [$auction, $generation] = $this->generation('becomes-stale');
        $pdf = new class($auction) implements BrowserDocumentRenderer {
            public int $calls = 0;
            public function __construct(private readonly Auction $auction) {}
            public function pdf(string $html, string $outputPath): string
            {
                file_put_contents($outputPath, '%PDF-generated');
                if (++$this->calls === 1) {
                    $this->auction->reportGenerations()->create([
                        'revision_token' => 'newer-revision', 'status' => 'queued', 'snapshot' => [],
                    ]);
                }
                return $outputPath;
            }
            public function png(string $html, string $outputPath): string { return $outputPath; }
        };

        $this->job($auction, $generation)->handle(
            app(AuctionReportDataBuilder::class), app(AuctionReportRenderer::class), $pdf,
        );

        self::assertSame('superseded', $generation->fresh()->status);
        self::assertSame([], Storage::disk('tenant_private')->allFiles());
    }

    private function successfulPdfRenderer(): BrowserDocumentRenderer
    {
        return new class implements BrowserDocumentRenderer {
            public function pdf(string $html, string $outputPath): string
            {
                file_put_contents($outputPath, '%PDF-success');
                return $outputPath;
            }
            public function png(string $html, string $outputPath): string { return $outputPath; }
        };
    }

    /** @return array{Auction, AuctionReportGeneration} */
    private function generation(string $token): array
    {
        $assignment = Assignment::query()->create([
            'assignment_number' => 'GEN-'.uniqid(), 'name' => 'إسناد توليد',
            'issuing_entity' => 'جهة اختبار', 'is_active' => true,
        ]);
        $auction = Auction::query()->create([
            'assignment_id' => $assignment->id, 'name' => 'مزاد توليد',
            'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض',
            'project_manager_name' => 'مدير المشروع', 'report_image' => 'report.jpg',
            'status' => 'completed', 'is_active' => false,
        ]);
        $snapshot = app(AuctionReportDataBuilder::class)->build($auction);
        $generation = $auction->reportGenerations()->create([
            'revision_token' => $token, 'status' => 'queued', 'snapshot' => $snapshot,
        ]);

        return [$auction, $generation];
    }

    private function job(Auction $auction, AuctionReportGeneration $generation): GenerateAuctionReportsJob
    {
        return new GenerateAuctionReportsJob(
            (string) tenant('id'), (int) $auction->id, (int) $generation->id, $generation->revision_token,
        );
    }
}

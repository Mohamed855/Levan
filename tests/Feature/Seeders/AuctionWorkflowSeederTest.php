<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\BrowsershotDocumentRenderer;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Database\Seeders\tenant\AuctionsAndPropertiesSeeder;
use Database\Seeders\tenant\AuctionWorkflowScenariosSeeder;
use Database\Seeders\tenant\Support\SeededAuctionDocuments;
use Database\Seeders\tenant\TenantDatabaseSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionWorkflowSeederTest extends TenantAuctionReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(BrowserDocumentRenderer::class, new class implements BrowserDocumentRenderer
        {
            public function pdf(string $html, string $outputPath): string
            {
                copy(database_path('seeders/fixtures/auction-demo-document.pdf'), $outputPath);

                return $outputPath;
            }

            public function png(string $html, string $outputPath): string
            {
                throw new \LogicException('Auction seed documents must be PDFs.');
            }
        });
    }

    public function test_demo_workflows_cover_approval_closing_completeness_and_ready_files_before_returning(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        $this->app->detectEnvironment(fn () => 'production');
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        Queue::fake();
        self::assertTrue(class_exists(AuctionWorkflowScenariosSeeder::class));
        $this->artisan('db:seed', ['--class' => AuctionWorkflowScenariosSeeder::class, '--force' => true])->assertSuccessful();
        $expected = [
            '01' => ['pending', 'incomplete', false, false],
            '02' => ['pending', 'incomplete', true, false],
            '03' => ['pending', 'complete', true, false],
            '04' => ['approved', 'incomplete', true, false],
            '05' => ['approved', 'complete', true, false],
            '06' => ['approved', 'complete', true, true],
            '07' => ['closed', 'complete', true, true],
            '08' => ['closed', 'incomplete', true, true],
            '09' => ['approved', 'incomplete', true, true],
            '10' => ['pending', 'incomplete', false, false],
        ];
        foreach ($expected as $number => [$status, $completion, $hasRequest, $hasClosing]) {
            $auction = Auction::where('name', 'like', '[WF-'.$number.']%')->firstOrFail();
            self::assertSame($status, $auction->status->value, $auction->name);
            self::assertSame($completion, $auction->completion_status->value, $auction->name);
            self::assertSame($hasRequest, $auction->establishmentRequest !== null);
            self::assertSame($hasClosing, $auction->closing !== null);
            self::assertNotNull($auction->latestReportGeneration);
            self::assertTrue($auction->latestReportGeneration->isReady());
            self::assertSame($auction->latestReportGeneration->id, $auction->published_report_generation_id);
            Storage::disk('tenant_private')->assertExists($auction->latestReportGeneration->outputs_path);
            Storage::disk('tenant_private')->assertExists($auction->latestReportGeneration->report_path);
            if ($hasRequest) {
                $form = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'establishment')->latest('id')->firstOrFail();
                self::assertSame('ready', $form->status);
                Storage::disk('tenant_private')->assertExists($form->file_path);
                self::assertStringContainsString('name="auction-regulatory-form"', app(AuctionRegulatoryForms::class)->html($form->snapshot, 'establishment'));
                foreach ($auction->establishmentRequest->data['attachments'] as $files) {
                    foreach ($files as $file) {
                        Storage::disk('tenant_private')->assertExists($file['path']);
                    }
                }
            }
            if ($hasClosing) {
                self::assertSame($auction->establishmentRequest->approval_number, $auction->closing->approval_number);
                self::assertDatabaseHas('auction_form_generations', ['auction_id' => $auction->id, 'type' => 'closing', 'status' => 'ready']);
                $form = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'closing')->latest('id')->firstOrFail();
                Storage::disk('tenant_private')->assertExists($form->file_path);
                self::assertStringContainsString('name="auction-regulatory-form"', app(AuctionRegulatoryForms::class)->html($form->snapshot, 'closing'));
            }
        }
        Queue::assertNothingPushed();
        self::assertSame(0, Auction::where('name', 'like', '[WF-10]%')->firstOrFail()->properties()->count());
        foreach (['01', '10'] as $number) {
            self::assertFalse(AuctionFormGeneration::where('auction_id', Auction::where('name', 'like', '[WF-'.$number.']%')->value('id'))->exists());
        }
    }

    public function test_disabled_demo_seeder_does_not_write_in_any_environment(): void
    {
        config(['seeding.test_seeders_enabled' => false]);
        $this->app->detectEnvironment(fn () => 'production');
        $count = Auction::count();
        self::assertTrue(class_exists(AuctionWorkflowScenariosSeeder::class));
        $this->artisan('db:seed', ['--class' => AuctionWorkflowScenariosSeeder::class, '--force' => true])->assertSuccessful();
        self::assertSame($count, Auction::count());
    }

    public function test_repeated_scenario_seed_preserves_tester_changes_without_duplicate_documents(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $auction = Auction::where('name', 'like', '[WF-05]%')->firstOrFail();
        $auction->update(['name' => 'Renamed by tester', 'description' => 'Preserve tester changes']);
        $empty = Auction::where('name', 'like', '[WF-10]%')->firstOrFail();
        $empty->update(['name' => 'Renamed empty auction']);
        $counts = [Auction::count(), AuctionFormGeneration::count(), count(Storage::disk('tenant_private')->allFiles())];
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        self::assertSame('Preserve tester changes', $auction->fresh()->description);
        self::assertSame('Renamed by tester', $auction->fresh()->name);
        self::assertSame('Renamed empty auction', $empty->fresh()->name);
        self::assertSame($counts, [Auction::count(), AuctionFormGeneration::count(), count(Storage::disk('tenant_private')->allFiles())]);
    }

    public function test_base_demo_seeder_keeps_property_coverage_and_links_existing_mixed_closing(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        Queue::fake();
        // Region lookup crosses tenancy contexts and would discard transactional fixtures.
        $regions = \Mockery::mock(new CentralCityOptionsGateway);
        $regions->shouldReceive('allRegionOptionsByName')->once()->andReturn([]);
        $this->app->instance(CentralCityOptionsGateway::class, $regions);
        $originalAuctionCount = Auction::count();
        $this->seed(TenantDatabaseSeeder::class);
        self::assertSame($originalAuctionCount, Auction::count(), 'Normal tenant bootstrap must not create demo auctions.');
        $this->seed(AuctionsAndPropertiesSeeder::class);
        foreach (Auction::where('name', 'not like', '[WF-%')->whereHas('attachments', fn ($query) => $query->where('file_path', 'auctions/attachments/sample_brochure.pdf'))->get() as $seededAuction) {
            self::assertTrue($seededAuction->latestReportGeneration?->isReady(), $seededAuction->name);
            Storage::disk('tenant_private')->assertExists($seededAuction->latestReportGeneration->outputs_path);
            Storage::disk('tenant_private')->assertExists($seededAuction->latestReportGeneration->report_path);
        }
        foreach (PropertyType::cases() as $type) {
            self::assertTrue(Property::withTrashed()->where('type', $type->value)->exists(), $type->value);
        }
        foreach (AssignmentCategory::cases() as $category) {
            self::assertTrue(Assignment::withTrashed()->where('category', $category->value)->exists(), $category->value);
        }
        foreach (PropertyUsageClassification::cases() as $usage) {
            self::assertTrue(Property::whereNull('assignment_id')->where('usage_classification', $usage->value)->exists(), $usage->value);
        }
        $auction = Auction::where('name', 'مزاد الشرقية الصناعي')->firstOrFail();
        self::assertSame('approved', $auction->status->value);
        self::assertTrue($auction->establishmentRequest->isApproved());
        self::assertNotNull($auction->closing);
        self::assertTrue($auction->properties->contains(fn ($property) => $property->pivot->result_status === 'sold'));
        self::assertTrue($auction->properties->contains(fn ($property) => $property->pivot->result_status === 'unsold'));
        Storage::disk('tenant_public')->assertExists('auctions/attachments/sample_brochure.pdf');
        $admin = User::where('email', 'demo@lot.com')->firstOrFail();
        self::assertTrue($admin->can('auction_establishment_requests.approve'));
        self::assertTrue($admin->can('auction_establishment_requests.regenerate'));
        Queue::assertNothingPushed();
    }

    public function test_missing_report_and_closing_files_are_recreated_without_changing_approved_data(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $auction = Auction::where('marketing_data->demo_scenario', 'WF-07')->firstOrFail();
        $report = $auction->latestReportGeneration;
        $closing = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'closing')->latest('id')->firstOrFail();
        $approvedData = $auction->establishmentRequest->data;
        $counts = [$auction->reportGenerations()->count(), AuctionFormGeneration::where('auction_id', $auction->id)->count()];
        Storage::disk('tenant_private')->delete([$report->report_path, $closing->file_path]);
        $documents = app(SeededAuctionDocuments::class);
        $documents->generate($auction);
        $latest = $auction->fresh()->latestReportGeneration;
        self::assertTrue($latest->isReady());
        self::assertNotSame($report->id, $latest->id);
        Storage::disk('tenant_private')->assertExists($latest->report_path);
        $closing = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'closing')->latest('id')->firstOrFail();
        Storage::disk('tenant_private')->assertExists($closing->file_path);
        self::assertSame($approvedData, $auction->fresh()->establishmentRequest->data);
        self::assertTrue($auction->fresh()->isClosed());
        self::assertSame([$counts[0] + 1, $counts[1] + 1], [$auction->reportGenerations()->count(), AuctionFormGeneration::where('auction_id', $auction->id)->count()]);
        $documents->generate($auction);
        self::assertSame([$counts[0] + 1, $counts[1] + 1], [$auction->reportGenerations()->count(), AuctionFormGeneration::where('auction_id', $auction->id)->count()]);
        Queue::assertNothingPushed();
    }

    public function test_renderer_failure_is_reported_and_rerunning_completes_the_failed_scenario(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $workingRenderer = app(BrowserDocumentRenderer::class);
        $this->app->instance(BrowserDocumentRenderer::class, new class implements BrowserDocumentRenderer
        {
            public function pdf(string $html, string $outputPath): string
            {
                throw new \RuntimeException('Seed renderer unavailable.');
            }

            public function png(string $html, string $outputPath): string
            {
                throw new \LogicException('Unexpected PNG render.');
            }
        });
        try {
            $this->seed(AuctionWorkflowScenariosSeeder::class);
            self::fail('Seeding must not report success when PDF rendering fails.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Seed renderer unavailable.', $exception->getMessage());
        }
        $auction = Auction::where('marketing_data->demo_scenario', 'WF-01')->firstOrFail();
        self::assertTrue($auction->latestReportGeneration->isFailed());
        $generationId = $auction->latestReportGeneration->id;
        $this->app->instance(BrowserDocumentRenderer::class, $workingRenderer);
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        self::assertTrue($auction->fresh()->latestReportGeneration->isReady());
        self::assertSame($generationId, $auction->fresh()->latestReportGeneration->id);
        self::assertSame(1, Auction::where('marketing_data->demo_scenario', 'WF-01')->count());
        Queue::assertNothingPushed();
    }

    public function test_real_chromium_generates_all_four_seeded_auction_documents(): void
    {
        if (getenv('AUCTION_SEED_RENDER_SMOKE') !== '1') {
            self::markTestSkipped('Enable AUCTION_SEED_RENDER_SMOKE to verify seeded files with real Chromium.');
        }
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $auction = Auction::where('marketing_data->demo_scenario', 'WF-07')->firstOrFail();
        $forms = AuctionFormGeneration::where('auction_id', $auction->id)->where('status', 'ready')->get();
        $report = $auction->latestReportGeneration;
        Storage::disk('tenant_private')->delete([$report->outputs_path, $report->report_path, ...$forms->pluck('file_path')->all()]);
        $this->app->instance(BrowserDocumentRenderer::class, app(BrowsershotDocumentRenderer::class));
        app(SeededAuctionDocuments::class)->generate($auction);
        $report = $auction->fresh()->latestReportGeneration;
        $files = ['outputs' => $report->outputs_path, 'report' => $report->report_path];
        foreach (['establishment', 'closing'] as $type) {
            $form = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', $type)->latest('id')->firstOrFail();
            self::assertTrue($form->isReady());
            $files[$type] = $form->file_path;
        }
        $directory = base_path('tmp/pdfs/seeded-auction-ready');
        File::ensureDirectoryExists($directory);
        foreach ($files as $name => $path) {
            $bytes = Storage::disk('tenant_private')->get($path);
            self::assertGreaterThan(10000, strlen($bytes));
            self::assertStringStartsWith('%PDF-', $bytes);
            self::assertStringContainsString('%%EOF', $bytes);
            file_put_contents($directory.'/'.$name.'.pdf', $bytes);
        }
        self::assertSame($auction->fresh()->published_report_generation_id, $report->id);
        Queue::assertNothingPushed();
    }

    public function test_existing_deferred_demo_titles_are_upgraded_without_rewriting_approved_request_data(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $pending = Auction::where('marketing_data->demo_scenario', 'WF-01')->firstOrFail();
        $pendingTitle = $pending->name;
        $pending->update(['name' => '[WF-01] قيد الانتظار بدون طلب إقامة', 'published_report_generation_id' => null]);
        $oldSnapshot = $pending->latestReportGeneration->snapshot;
        $oldSnapshot['auction']['name'] = $pending->name;
        $pending->latestReportGeneration->update(['status' => 'queued', 'snapshot' => $oldSnapshot, 'outputs_path' => null, 'report_path' => null]);
        $auction = Auction::where('marketing_data->demo_scenario', 'WF-07')->firstOrFail();
        $shortName = $auction->name;
        $legacyName = '[WF-07] تم الإقفال والتسويق مكتمل';
        $auction->update(['name' => $legacyName, 'published_report_generation_id' => null]);
        $closing = $auction->closing;
        $snapshot = $closing->finalized_snapshot;
        $snapshot['auction']['name'] = $legacyName;
        $closing->update(['finalized_snapshot' => $snapshot]);
        $report = $auction->latestReportGeneration;
        $report->update(['status' => 'queued', 'snapshot' => $snapshot, 'outputs_path' => null, 'report_path' => null]);
        $requestData = $auction->establishmentRequest->data;
        $requestData['auction']['name'] = $legacyName;
        $auction->establishmentRequest->update(['data' => $requestData]);
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        self::assertSame($pendingTitle, $pending->fresh()->name);
        self::assertTrue($pending->fresh()->latestReportGeneration->isReady());
        $auction->refresh();
        self::assertSame($shortName, $auction->name);
        self::assertTrue($auction->latestReportGeneration->isReady());
        self::assertSame($shortName, $auction->latestReportGeneration->snapshot['auction']['name']);
        self::assertSame($requestData, $auction->establishmentRequest->data);
        self::assertTrue($auction->isClosed());
        $snapshot['auction']['name'] = $shortName;
        self::assertSame($snapshot, $auction->closing->finalized_snapshot);
    }

    public function test_corrupt_ready_pdfs_are_regenerated_instead_of_reused(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $auction = Auction::where('marketing_data->demo_scenario', 'WF-07')->firstOrFail();
        $oldReport = $auction->latestReportGeneration;
        $oldForm = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'closing')->latest('id')->firstOrFail();
        Storage::disk('tenant_private')->put($oldReport->report_path, 'Not a PDF');
        Storage::disk('tenant_private')->put($oldForm->file_path, '%PDF-1.4 truncated');
        app(SeededAuctionDocuments::class)->generate($auction);
        $report = $auction->fresh()->latestReportGeneration;
        $form = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'closing')->latest('id')->firstOrFail();
        self::assertNotSame($oldReport->id, $report->id);
        self::assertNotSame($oldForm->id, $form->id);
        self::assertTrue($report->isReady());
        self::assertTrue($form->isReady());
        self::assertStringContainsString('%%EOF', Storage::disk('tenant_private')->get($report->report_path));
        self::assertStringContainsString('%%EOF', Storage::disk('tenant_private')->get($form->file_path));
    }
}

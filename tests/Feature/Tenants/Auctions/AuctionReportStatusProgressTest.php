<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\Enums\Tenants\Auctions\AuctionFilePreparationStatus;
use App\Http\Controllers\Tenants\Auctions\AuctionReportController;
use App\Http\Requests\Tenants\Auctions\RetryAuctionReportsRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class AuctionReportStatusProgressTest extends TestCase
{
    public function test_auction_report_generation_stores_progress(): void
    {
        $generation = new AuctionReportGeneration([
            'progress' => 60,
            'status' => 'processing',
        ]);

        $this->assertSame(60, $generation->progress);
        $this->assertTrue($generation->isProcessing());
        $this->assertFalse($generation->isReady());
    }

    public function test_retry_request_authorization_rules(): void
    {
        $request = new RetryAuctionReportsRequest;
        $this->assertSame([], $request->rules());
    }

    public function test_auction_file_preparation_status_resolves_strictly_into_three_approved_states(): void
    {
        $this->assertSame('لم يتم التجهيز', AuctionFilePreparationStatus::NOT_PREPARED->label());
        $this->assertSame('فشل التجهيز', AuctionFilePreparationStatus::FAILED->label());
        $this->assertSame('جاهز للتحميل', AuctionFilePreparationStatus::READY->label());

        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve(null, false));
        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve('queued', false));
        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve('queued', true));
        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve('processing', false));
        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve('processing', true));
        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve('superseded', false));
        $this->assertSame(AuctionFilePreparationStatus::NOT_PREPARED, AuctionFilePreparationStatus::resolve('ready', false));

        $this->assertSame(AuctionFilePreparationStatus::FAILED, AuctionFilePreparationStatus::resolve('failed', false));
        $this->assertSame(AuctionFilePreparationStatus::FAILED, AuctionFilePreparationStatus::resolve('failed', true));

        $this->assertSame(AuctionFilePreparationStatus::READY, AuctionFilePreparationStatus::resolve('ready', true));
        $this->assertSame(AuctionFilePreparationStatus::READY, AuctionFilePreparationStatus::resolve('superseded', true));

        $clientMap = AuctionFilePreparationStatus::clientMap();
        $this->assertSame(['not_prepared', 'failed', 'ready'], array_keys($clientMap));
    }

    public function test_auctions_datatable_outputs_column_uses_three_approved_states_and_matches_show_page(): void
    {
        $dataTable = new AuctionsDataTable;
        $fakeDataTable = new class
        {
            public array $addedColumns = [];

            public array $editedColumns = [];

            public function setRowId($callback): self
            {
                return $this;
            }

            public function addColumn(string $name, $callback): self
            {
                $this->addedColumns[$name] = $callback;

                return $this;
            }

            public function editColumn(string $name, $callback): self
            {
                $this->editedColumns[$name] = $callback;

                return $this;
            }
        };

        $reflection = new \ReflectionClass($dataTable);
        $method = $reflection->getMethod('addCustomColumns');
        $method->setAccessible(true);
        $method->invoke($dataTable, $fakeDataTable);

        $this->assertArrayHasKey('outputs', $fakeDataTable->addedColumns);
        $callback = $fakeDataTable->addedColumns['outputs'];

        $publishedReady = new AuctionReportGeneration([
            'status' => 'ready',
            'outputs_path' => 'outputs.pdf',
            'report_path' => 'report.pdf',
        ]);

        // 1. No generation yet -> text-muted — (matching closing_model column in auction closings index)
        $rowNone = (object) ['latestReportGeneration' => null, 'publishedReportGeneration' => null];
        $this->assertStringContainsString('text-muted', $callback($rowNone));
        $this->assertStringContainsString('—', $callback($rowNone));

        // 2. Actively queued/processing -> text-muted —
        $rowProcessing = (object) [
            'latestReportGeneration' => new AuctionReportGeneration(['status' => 'processing']),
            'publishedReportGeneration' => $publishedReady,
        ];
        $this->assertStringContainsString('text-muted', $callback($rowProcessing));
        $this->assertStringContainsString('—', $callback($rowProcessing));

        // 3. Failed -> text-muted —
        $rowFailed = (object) [
            'latestReportGeneration' => new AuctionReportGeneration(['status' => 'failed']),
            'publishedReportGeneration' => $publishedReady,
        ];
        $this->assertStringContainsString('text-muted', $callback($rowFailed));
        $this->assertStringContainsString('—', $callback($rowFailed));

        // 4. Ready & published -> download badges when paths exist
        $rowReady = (object) [
            'id' => 123,
            'latestReportGeneration' => $publishedReady,
            'publishedReportGeneration' => $publishedReady,
        ];
        $this->assertStringContainsString('المخرجات', $callback($rowReady));
        $this->assertStringContainsString('التقرير', $callback($rowReady));
    }

    public function test_show_and_regulatory_forms_views_remove_fake_progress_and_unify_status_labels(): void
    {
        $showContent = file_get_contents(resource_path('views/tenant/auctions/show.blade.php'));
        $formsContent = file_get_contents(resource_path('views/tenant/auctions/partials/regulatory-forms.blade.php'));
        $helpersContent = file_get_contents(resource_path('views/tenant/auctions/partials/_generation-helpers.blade.php'));

        foreach ([$showContent, $formsContent, $helpersContent] as $content) {
            $this->assertIsString($content);
            $this->assertStringNotContainsString('simulatedProgress', $content);
            $this->assertStringNotContainsString('|| 25', $content);
            $this->assertStringNotContainsString('Math.max(5,', $content);
            $this->assertStringNotContainsString('max(6,', $content);
            $this->assertStringNotContainsString('غير مجهز بعد', $content);
            $this->assertStringNotContainsString('فشل الإعداد', $content);
            $this->assertStringNotContainsString('قيد التوليد', $content);
            $this->assertStringNotContainsString('>انتهت المهلة<', $content);
            $this->assertStringNotContainsString("'انتهت المهلة'", $content);
        }

        // Preserve existing contract hooks checked by test suites
        $this->assertStringContainsString('startPolling(AUTO_TIMEOUT_MS);', $showContent);
        $this->assertStringContainsString('reports-progress-bar', $showContent);
        $this->assertStringContainsString('تحضير وتجهيز تقارير المزاد', $showContent);
        $this->assertStringContainsString('createProgressTracker', $helpersContent);

        $renderedHelpers = view('tenant.auctions.partials._generation-helpers')->render();
        $this->assertStringContainsString('\u0644\u0645 \u064a\u062a\u0645 \u0627\u0644\u062a\u062c\u0647\u064a\u0632', $renderedHelpers);
        $this->assertStringContainsString('\u0641\u0634\u0644 \u0627\u0644\u062a\u062c\u0647\u064a\u0632', $renderedHelpers);
        $this->assertStringContainsString('\u062c\u0627\u0647\u0632 \u0644\u0644\u062a\u062d\u0645\u064a\u0644', $renderedHelpers);
    }

    public function test_report_controller_status_and_retry_contract(): void
    {
        Gate::shouldReceive('authorize')->with('view', \Mockery::type(Auction::class))->andReturnTrue();

        $controller = app(AuctionReportController::class);

        $published = new AuctionReportGeneration([
            'status' => 'ready',
            'progress' => 100,
            'outputs_path' => 'outputs.pdf',
            'report_path' => 'report.pdf',
        ]);
        $published->id = 10;

        $failedNext = new AuctionReportGeneration([
            'status' => 'failed',
            'progress' => 0,
            'error_message' => 'Internal chromium failure',
        ]);
        $failedNext->id = 11;

        $auction = new Auction;
        $auction->setRelation('latestReportGeneration', $failedNext);
        $auction->setRelation('publishedReportGeneration', $published);

        $data = $controller->status($auction)->getData(true);
        $this->assertSame('failed', $data['status']);
        $this->assertSame(0, $data['progress']);
        $this->assertSame(11, $data['generation_id']);
        $this->assertTrue($data['has_outputs']);
        $this->assertTrue($data['has_report']);
        $this->assertSame('تعذر تجهيز ملفات المزاد. يرجى المحاولة مرة أخرى.', $data['error_message']);

        $controllerSource = file_get_contents(app_path('Http/Controllers/Tenants/Auctions/AuctionReportController.php'));
        $this->assertIsString($controllerSource);
        $this->assertStringContainsString('startPendingGeneration($auction, $tenantId)', $controllerSource);
        $this->assertStringContainsString('publicErrorMessage()', $controllerSource);
    }
}

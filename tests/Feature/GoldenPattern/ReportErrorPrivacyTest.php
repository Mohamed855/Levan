<?php

declare(strict_types=1);

namespace Tests\Feature\GoldenPattern;

use App\Http\Controllers\Tenants\Auctions\AuctionReportController;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class ReportErrorPrivacyTest extends TestCase
{
    public function test_error_widget_renders_without_a_first_generation(): void
    {
        $html = view('tenant.auctions.partials.report-error', ['generation' => null])->render();
        $this->assertStringContainsString('reports-error-text', $html);
        $this->assertStringContainsString('تعذر تجهيز ملفات المزاد', $html);
    }

    public function test_status_does_not_expose_internal_renderer_errors_from_existing_records(): void
    {
        Gate::shouldReceive('authorize')->once()->with('view', \Mockery::type(Auction::class))->andReturnTrue();
        $auction = new Auction();
        $generation = new AuctionReportGeneration([
            'status' => 'failed', 'error_message' => 'Process /private/renderer --password=secret exited 1',
        ]);
        $auction->setRelation('latestReportGeneration', $generation);
        $data = app(AuctionReportController::class)->status($auction)->getData(true);
        $this->assertSame('تعذر تجهيز ملفات المزاد. يرجى المحاولة مرة أخرى.', $data['error_message']);
        $this->assertStringNotContainsString('secret', json_encode($data));
    }
}

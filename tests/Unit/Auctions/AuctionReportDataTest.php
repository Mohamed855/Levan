<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportDataTest extends TenantAuctionReportTestCase
{
    public function test_snapshot_uses_recorded_results_and_preserves_pending_values_as_null(): void
    {
        self::assertTrue(class_exists(AuctionReportDataBuilder::class), 'AuctionReportDataBuilder is not implemented.');

        $assignment = Assignment::query()->create([
            'assignment_number' => 'REPORT-DATA-'.uniqid(),
            'name' => 'إسناد بيانات التقرير',
            'issuing_entity' => 'جهة اختبار',
            'is_active' => true,
        ]);
        $sold = $this->property($assignment, 'SOLD');
        $pending = $this->property($assignment, 'PENDING');
        $auction = Auction::query()->create([
            'assignment_id' => $assignment->id,
            'name' => 'مزاد بيانات ديناميكي',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'الرياض',
            'status' => 'completed',
            'is_active' => false,
        ]);
        $auction->setAttribute('project_manager_name', 'مدير المشروع');
        $auction->setAttribute('report_image', 'auctions/reports/report.webp');
        $auction->properties()->sync([
            $sold->id => [
                'sort_order' => 1,
                'result_status' => 'sold',
                'opening_price' => 100000,
                'sale_price' => 125000,
                'participants_count' => 4,
                'bids_count' => 9,
            ],
            $pending->id => ['sort_order' => 2, 'result_status' => 'pending'],
        ]);

        $snapshot = app(AuctionReportDataBuilder::class)->build($auction);

        self::assertSame(2, $snapshot['totals']['properties_count']);
        self::assertSame(1, $snapshot['totals']['sold_count']);
        self::assertSame(1, $snapshot['totals']['pending_count']);
        self::assertSame(125000.0, $snapshot['totals']['recorded_sales_total']);
        self::assertNull($snapshot['properties'][1]['sale_price']);
        self::assertSame('مزاد بيانات ديناميكي', $snapshot['auction']['name']);
    }

    private function property(Assignment $assignment, string $suffix): Property
    {
        return Property::query()->create([
            'assignment_id' => $assignment->id,
            'property_number' => 'REPORT-'.$suffix.'-'.uniqid(),
            'name' => 'عقار '.$suffix,
            'type' => 'residential_land',
            'city' => 'الرياض',
            'area' => 100,
            'estimated_value' => 100000,
        ]);
    }
}

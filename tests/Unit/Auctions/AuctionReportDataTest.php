<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Illuminate\Support\Facades\Storage;
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

    public function test_snapshot_preserves_sort_order_deeds_gallery_and_all_report_evidence(): void
    {
        Storage::fake('tenant_public');
        foreach (['report.jpg', 'main-a.jpg', 'main-b.jpg', 'gallery.jpg', 'marketing.jpg', 'before.jpg', 'after.jpg'] as $file) {
            Storage::disk('tenant_public')->put($file, 'image-'.$file);
        }

        $assignment = Assignment::query()->create([
            'assignment_number' => 'REPORT-FULL-'.uniqid(), 'name' => 'إسناد كامل',
            'issuing_entity' => 'جهة اختبار', 'is_active' => true,
        ]);
        $first = $this->property($assignment, 'FIRST');
        $first->update(['deed_number' => 'DEED-001', 'main_image' => 'main-a.jpg']);
        $second = $this->property($assignment, 'SECOND');
        $second->update(['deed_number' => 'DEED-002', 'main_image' => 'main-b.jpg']);
        $first->attachments()->create([
            'disk' => 'tenant_public', 'file_path' => 'gallery.jpg', 'original_name' => 'gallery.jpg',
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_size' => 12,
            'category' => PropertyAttachmentCategory::GALLERY_IMAGE->value,
        ]);

        $auction = Auction::query()->create([
            'assignment_id' => $assignment->id, 'name' => 'مزاد كامل',
            'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض',
            'project_manager_name' => 'مدير المشروع', 'report_image' => 'report.jpg',
            'status' => 'completed', 'is_active' => false,
        ]);
        $auction->properties()->sync([
            $first->id => ['sort_order' => 2, 'result_status' => 'pending'],
            $second->id => ['sort_order' => 1, 'result_status' => 'sold', 'sale_price' => 10200000],
        ]);
        foreach ([
            ['marketing_evidence', null, 'marketing.jpg'],
            ['board_before', $second->id, 'before.jpg'],
            ['board_after', $second->id, 'after.jpg'],
        ] as $index => [$section, $propertyId, $path]) {
            $auction->attachments()->create([
                'file_path' => $path, 'file_name' => $path, 'file_type' => 'jpg', 'file_size' => 12,
                'category' => 'marketing', 'report_section' => $section,
                'property_id' => $propertyId, 'display_order' => $index, 'channels' => ['report'],
            ]);
        }

        $snapshot = app(AuctionReportDataBuilder::class)->build($auction);

        self::assertSame([$second->id, $first->id], array_column($snapshot['properties'], 'id'));
        self::assertSame('DEED-002', $snapshot['properties'][0]['deed_number']);
        self::assertCount(2, $snapshot['properties'][1]['images']);
        self::assertNotNull($snapshot['auction']['report_image']);
        self::assertCount(1, $snapshot['evidence']['marketing_evidence']);
        self::assertCount(1, $snapshot['evidence']['board_before']);
        self::assertCount(1, $snapshot['evidence']['board_after']);
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

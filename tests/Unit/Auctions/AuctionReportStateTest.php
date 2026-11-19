<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\Reports\AuctionReportState;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

final class AuctionReportStateTest extends TestCase
{
    public function test_incomplete_auction_when_nullable_or_marketing_fields_are_missing(): void
    {
        $auction = $this->completeAuction();
        $auction->platform_url = null;

        self::assertSame('incomplete', (new AuctionReportState)->forAuction($auction)->value);
    }

    public function test_complete_auction_when_all_steps_one_through_five_are_filled(): void
    {
        $auction = $this->completeAuction();

        self::assertSame('complete', (new AuctionReportState)->forAuction($auction)->value);
    }

    public function test_missing_step4_field_or_attachment_marks_auction_incomplete(): void
    {
        $auction = $this->completeAuction();
        $marketing = $auction->marketing_data;
        $marketing['city_context'] = [];
        $auction->marketing_data = $marketing;

        self::assertSame('incomplete', (new AuctionReportState)->forAuction($auction)->value);
    }

    private function completeAuction(): Auction
    {
        $auction = new Auction([
            'assignment_id' => 1,
            'name' => 'مزاد مكتمل',
            'description' => 'وصف داخلي للمزاد',
            'supervising_entity' => 'إنفاذ',
            'start_date' => '2026-11-10',
            'start_time' => '09:00',
            'end_date' => '2026-11-12',
            'end_time' => '17:00',
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'الرياض',
            'expected_properties_count' => 1,
            'platform_entity_id' => 2,
            'platform_url' => 'https://example.com/auction',
            'project_manager_name' => 'أحمد',
            'notes' => 'ملاحظات المنصة',
            'report_image' => 'auctions/report-images/cover.png',
            'cover_image' => 'auctions/covers/cover.png',
            'marketing_data' => [
                'planning_methodology' => 'تخطيط',
                'city_context' => ['منطقة الرياض'],
                'brochure_methodology' => 'كتيب',
                'audience_methodology' => 'فئات',
                'messages_methodology' => 'رسائل',
                'brochure_context' => 'تفاصيل الكتيب',
                'brochure_url' => 'https://example.com/brochure',
                'board_context' => 'تفاصيل اللوحات',
                'board_url' => 'https://example.com/boards',
                'contract_text' => 'نص التعاقد',
                'brokers' => [['key' => 'b-1', 'name' => 'وسيط 1']],
                'introduction' => 'مقدمة التقرير',
                'report_summary' => 'ملخص التقرير',
            ],
        ]);
        $auction->setRelation('properties', new Collection([new Property(['id' => 1])]));
        $auction->setRelation('attachments', new Collection([
            new AuctionAttachment(['category' => 'marketing', 'report_section' => 'brochure']),
            new AuctionAttachment(['category' => 'marketing', 'report_section' => 'platform']),
            new AuctionAttachment(['category' => 'marketing', 'report_section' => 'brokers', 'report_metadata' => ['broker_key' => 'b-1']]),
            new AuctionAttachment(['category' => 'auction_doc']),
        ]));

        return $auction;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Jobs\Tenants\Auctions\GenerateAuctionRegulatoryFormJob;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\BrowsershotDocumentRenderer;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormDataBuilder;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionUnifiedFormRegenerationTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_create_and_edit_queue_the_establishment_form_alongside_reports(): void
    {
        Queue::fake();
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        $assignment = Assignment::create(['name' => 'إسناد النماذج', 'assignment_number' => 'FORM-'.uniqid(), 'issuing_entity' => 'جهة', 'is_active' => true]);
        $property = Property::create(['assignment_id' => $assignment->id, 'property_number' => 'FORM-P-'.uniqid(), 'name' => 'عقار النموذج', 'type' => 'residential_land', 'city' => 'الرياض']);
        $data = [
            'assignment_id' => $assignment->id, 'name' => 'مزاد قبل التعديل', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض', 'property_ids' => [$property->id],
            'funds' => ['receipt_method' => 'certified_cheque', 'refund_method' => 'bank_transfer', 'entry_type' => 'refundable'],
            'properties_data' => [$property->id => ['opening_value' => 1000, 'entry_amount' => 0, 'ownership_share' => 100, 'board_count' => 1, 'board_dimensions' => '3x6', 'disclosure' => 'لا يوجد']],
        ];
        $auction = app(CreateAuctionAction::class)->execute($data);
        $first = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'establishment')->sole();
        self::assertSame('queued', $first->status);
        self::assertSame('cheque', $first->snapshot['funds']['receipt_method']);
        self::assertSame('transfer', $first->snapshot['funds']['refund_method']);
        self::assertNotEmpty(app(AuctionRegulatoryForms::class)->html($first->snapshot, 'establishment'));

        $data['name'] = 'مزاد بعد التعديل';
        $updated = app(UpdateAuctionAction::class)->execute($auction, $data);
        $latest = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'establishment')->latest('id')->firstOrFail();
        self::assertNotSame($first->id, $latest->id);
        self::assertSame('queued', $latest->status);
        self::assertSame('مزاد بعد التعديل', $latest->snapshot['auction']['name']);
        self::assertSame('مزاد قبل التعديل', $first->fresh()->snapshot['auction']['name']);
        self::assertSame(2, $updated->reportGenerations()->count());
        Queue::assertNothingPushed();
        (new GenerateAuctionRegulatoryFormJob((string) tenant('id'), $auction->id, $latest->id, $latest->revision_token))
            ->handle(app(AuctionRegulatoryForms::class), app(BrowsershotDocumentRenderer::class));
        self::assertSame('ready', $latest->fresh()->status);
        self::assertStringStartsWith('%PDF-', Storage::disk('tenant_private')->get($latest->fresh()->file_path));
    }

    public function test_edit_without_a_separate_request_queues_a_form_from_saved_auction_data(): void
    {
        $auction = $this->cycleAuction();
        app(UpdateAuctionAction::class)->execute($auction, ['name' => 'تعديل دون طلب']);
        $generation = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'establishment')->sole();
        self::assertSame('تعديل دون طلب', $generation->snapshot['auction']['name']);
        self::assertSame($auction->properties()->first()->id, $generation->snapshot['properties'][0]['property_id']);
    }

    public function test_saved_payment_aliases_are_normalized_without_changing_the_saved_request(): void
    {
        $auction = $this->cycleAuction();
        $request = $auction->establishmentRequest()->create(['submitted_at' => now(), 'data' => ['funds' => ['receipt_method' => 'sadad', 'refund_method' => 'pos']]]);
        $snapshot = app(AuctionRegulatoryFormDataBuilder::class)->build($auction->fresh(), 'establishment');
        self::assertSame('other', $snapshot['funds']['receipt_method']);
        self::assertSame('سداد', $snapshot['funds']['receipt_other']);
        self::assertSame('other', $snapshot['funds']['refund_method']);
        self::assertSame('نقاط بيع (مدى)', $snapshot['funds']['refund_other']);
        self::assertNotEmpty(app(AuctionRegulatoryForms::class)->html($snapshot, 'establishment'));
        self::assertSame('sadad', $request->fresh()->data['funds']['receipt_method']);
    }
}

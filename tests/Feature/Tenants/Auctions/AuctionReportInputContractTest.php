<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\AuctionReportInputService;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportInputContractTest extends TenantAuctionReportTestCase
{
    public function test_upload_metadata_survives_storage_and_snapshot_generation(): void
    {
        Storage::fake('tenant_public');
        $auction = $this->auction();
        $paths = [];
        app(AuctionReportInputService::class)->storeEvidence($auction, [UploadedFile::fake()->image('campaign.png')], [], [], [], $paths, [
            0 => ['section' => 'x', 'channel' => 'x', 'kind' => 'screenshot', 'display_name' => 'دليل الحملة', 'fit' => 'contain', 'focal_x' => 20, 'focal_y' => 40],
        ]);
        $attachment = $auction->attachments()->sole();
        self::assertSame('x', $attachment->report_section);
        self::assertSame('screenshot', $attachment->report_metadata['kind']);
        $evidence = app(AuctionReportDataBuilder::class)->build($auction->fresh())['evidence']['x'][0];
        self::assertSame('x', $evidence['channel']);
        self::assertSame('دليل الحملة', $evidence['display_name']);
        self::assertSame('contain', $evidence['fit']);
        self::assertSame(20, $evidence['focal_x']);
    }

    public function test_metadata_can_be_edited_without_uploading_the_file_again(): void
    {
        Storage::fake('tenant_public');
        $auction = $this->auction();
        $paths = [];
        $service = app(AuctionReportInputService::class);
        $service->storeEvidence($auction, [UploadedFile::fake()->image('campaign.png')], [], [], [], $paths);
        $attachment = $auction->attachments()->sole();
        $service->syncEvidenceMetadata($auction, [$attachment->id => ['section' => 'brokers', 'display_name' => 'اسم الوسيط', 'kind' => 'document', 'fit' => 'contain', 'display_order' => 4]]);
        self::assertSame($paths[0], $attachment->fresh()->file_path);
        self::assertSame('brokers', $attachment->fresh()->report_section);
        self::assertSame('اسم الوسيط', $attachment->fresh()->report_metadata['display_name']);
    }

    public function test_a_channel_classification_routes_generic_evidence_to_its_own_section(): void
    {
        Storage::fake('tenant_public');
        $auction = $this->auction();
        $paths = [];
        app(AuctionReportInputService::class)->storeEvidence($auction, [UploadedFile::fake()->image('youtube.png')], [], [], [], $paths, [0 => ['channel' => 'youtube', 'section' => 'marketing_evidence']]);
        self::assertSame('video', $auction->attachments()->sole()->report_section);
    }

    public function test_an_attachment_from_another_auction_cannot_be_reclassified(): void
    {
        Storage::fake('tenant_public');
        $first = $this->auction();
        $other = $this->auction();
        $paths = [];
        $service = app(AuctionReportInputService::class);
        $service->storeEvidence($other, [UploadedFile::fake()->image('foreign.png')], [], [], [], $paths);
        $id = $other->attachments()->sole()->id;
        $this->expectException(ValidationException::class);
        $service->syncEvidenceMetadata($first, [$id => ['section' => 'x', 'channel' => 'x']]);
    }

    public function test_form_request_rejects_unknown_channels_and_out_of_range_crop_positions(): void
    {
        $request = StoreAuctionRequest::create('/', 'POST', ['property_ids' => []]);
        $rules = array_filter($request->rules(), static fn (string $key): bool => str_starts_with($key, 'marketing_evidence_meta'), ARRAY_FILTER_USE_KEY);
        $validator = Validator::make(['marketing_evidence_meta' => [['channel' => 'javascript', 'focal_x' => 101, 'kind' => 'executable']]], $rules);
        self::assertTrue($validator->fails());
        self::assertTrue($validator->errors()->has('marketing_evidence_meta.0.channel'));
        self::assertTrue($validator->errors()->has('marketing_evidence_meta.0.focal_x'));
    }

    public function test_board_dimensions_and_zero_count_survive_partial_result_updates(): void
    {
        $auction = $this->auction();
        $property = Property::query()->create(['assignment_id' => $auction->assignment_id, 'property_number' => 'BOARD-'.uniqid(), 'name' => 'عقار', 'type' => 'residential_land', 'city' => 'الرياض']);
        $auction->properties()->attach($property->id, ['board_data' => json_encode(['location' => 'شارع رئيسي', 'notes' => 'ملاحظة محفوظة']), 'result_status' => 'pending']);
        $pivot = app(AuctionReportInputService::class)->pivotData([$property->id], [$property->id => ['status' => 'pending', 'board_count' => 0, 'board_dimensions' => '3×6']], $auction->properties()->get())[$property->id];
        $board = json_decode($pivot['board_data'], true);
        self::assertSame(0, $board['count']);
        self::assertSame('3×6', $board['dimensions']);
        self::assertSame('شارع رئيسي', $board['location']);
        self::assertSame('ملاحظة محفوظة', $board['notes']);
    }

    public function test_detailed_marketing_fields_are_validated_and_returned_in_the_validated_payload(): void
    {
        $request = StoreAuctionRequest::create('/', 'POST', ['property_ids' => []]);
        $rules = array_filter($request->rules(), static fn (string $key): bool => str_starts_with($key, 'marketing'), ARRAY_FILTER_USE_KEY);
        $data = ['marketing' => ['introduction' => 'مقدمة التقرير', 'results_narrative' => 'نتائج تفصيلية', 'contacts' => ['phone' => '0534811800'], 'campaigns' => [['platform' => 'whatsapp', 'messages' => -1, 'delivered' => 0, 'watch_hours' => 2.5]]]];
        $invalid = Validator::make($data, $rules);
        self::assertTrue($invalid->errors()->has('marketing.campaigns.0.messages'));
        $data['marketing']['campaigns'][0]['messages'] = 0;
        $valid = Validator::make($data, $rules);
        self::assertFalse($valid->fails());
        self::assertSame(0, $valid->validated()['marketing']['campaigns'][0]['messages']);
        self::assertSame('0534811800', $valid->validated()['marketing']['contacts']['phone']);
    }

    public function test_report_settings_expose_shared_controls_for_details_and_existing_evidence(): void
    {
        Storage::fake('tenant_public');
        $auction = $this->auction();
        $auction->update(['marketing_data' => ['contacts' => ['phone' => '0534811800'], 'campaigns' => [['platform' => 'snapchat', 'views' => 0]]]]);
        $paths = [];
        app(AuctionReportInputService::class)->storeEvidence($auction, [UploadedFile::fake()->image('proof.png')], [], [], [], $paths);
        $attachment = $auction->attachments()->sole();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = view('tenant.auctions.partials.steps.step4-settings', ['auction' => $auction->fresh()->load('properties', 'attachments')])->render();
        foreach (['marketing[introduction]', 'marketing[results_narrative]', 'marketing[contacts][phone]', 'marketing[whatsapp_date]', 'marketing[campaigns][0][views]', 'report_attachments['.$attachment->id.'][section]', 'marketing_evidence_meta[__INDEX__][channel]'] as $name) {
            self::assertTrue(str_contains($html, 'name="'.$name.'"'), 'Missing report control: '.$name);
        }
        self::assertTrue(str_contains($html, 'value="0534811800"'));
        self::assertTrue(str_contains($html, 'data-field-name="marketing[campaigns][0][views]"'));
    }

    public function test_whatsapp_total_must_agree_with_complete_delivery_counts_on_create_and_edit(): void
    {
        foreach ([StoreAuctionRequest::class, \App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest::class] as $class) {
            $request = $class::create('/', 'POST', ['property_ids' => []]);
            $rules = array_filter($request->rules(), static fn (string $key): bool => str_starts_with($key, 'marketing'), ARRAY_FILTER_USE_KEY);
            $campaign = ['platform' => 'whatsapp', 'delivered' => 70, 'failed' => 20, 'pending' => 10, 'total' => 101];
            $invalid = Validator::make(['marketing' => ['campaigns' => [$campaign]]], $rules);
            self::assertTrue($invalid->errors()->has('marketing.campaigns.0'), 'An inconsistent delivery total must fail before saving.');
            $campaign['total'] = 100;
            self::assertFalse(Validator::make(['marketing' => ['campaigns' => [$campaign]]], $rules)->fails());
            $campaign = ['platform' => 'whatsapp', 'delivered' => 0, 'failed' => 0, 'pending' => 0, 'total' => 0];
            self::assertFalse(Validator::make(['marketing' => ['campaigns' => [$campaign]]], $rules)->fails());
            $campaign['failed'] = null;
            $campaign['total'] = 10;
            self::assertFalse(Validator::make(['marketing' => ['campaigns' => [$campaign]]], $rules)->fails(), 'Unknown counts must not be silently treated as zero.');
        }
    }

    private function auction(): Auction
    {
        $assignment = Assignment::query()->create(['assignment_number' => 'CI-'.uniqid(), 'name' => 'إسناد اختبار', 'issuing_entity' => 'اختبار', 'is_active' => true]);

        return Auction::query()->create(['assignment_id' => $assignment->id, 'name' => 'مزاد اختبار', 'start_date' => '2026-05-19', 'end_date' => '2026-05-21', 'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض', 'is_active' => false]);
    }
}

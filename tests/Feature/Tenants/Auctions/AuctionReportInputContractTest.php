<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
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

    private function auction(): Auction
    {
        $assignment = Assignment::query()->create(['assignment_number' => 'CI-'.uniqid(), 'name' => 'إسناد اختبار', 'issuing_entity' => 'اختبار', 'is_active' => true]);

        return Auction::query()->create(['assignment_id' => $assignment->id, 'name' => 'مزاد اختبار', 'start_date' => '2026-05-19', 'end_date' => '2026-05-21', 'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض', 'status' => 'completed', 'is_active' => false]);
    }
}

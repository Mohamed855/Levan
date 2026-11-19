<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Models\Tenants\Assignments\Assignment;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionBrochureAttachmentUrlTest extends TenantAuctionReportTestCase
{
    public function test_brochure_url_is_derived_from_the_upload_on_create_and_replacement(): void
    {
        Storage::fake('tenant_public');
        $assignment = Assignment::create(['name' => 'إسناد الكتيب', 'assignment_number' => 'BOOK-'.uniqid(), 'issuing_entity' => 'جهة', 'is_active' => true]);
        $auction = app(CreateAuctionAction::class)->execute([
            'assignment_id' => $assignment->id, 'name' => 'مزاد الكتيب', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض', 'marketing' => ['brochure_url' => 'https://manual.example/ignored'],
        ], brochureEvidence: [UploadedFile::fake()->create('book.pdf', 10, 'application/pdf')]);
        $original = $auction->attachments()->where('report_section', 'brochure')->sole();
        self::assertSame(url(Storage::disk('tenant_public')->url($original->file_path)), $auction->marketing_data['brochure_url']);
        self::assertSame($auction->marketing_data['brochure_url'], app(AuctionReportDataBuilder::class)->build($auction)['marketing']['brochure_url']);

        $auction = app(UpdateAuctionAction::class)->execute($auction, ['remove_brochure_evidence' => [$original->id]], brochureEvidence: [UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf')]);
        $replacement = $auction->attachments()->where('report_section', 'brochure')->sole();
        self::assertNotSame($original->file_path, $replacement->file_path);
        self::assertSame(url(Storage::disk('tenant_public')->url($replacement->file_path)), $auction->marketing_data['brochure_url']);

        $auction = app(UpdateAuctionAction::class)->execute($auction, ['remove_brochure_evidence' => [$replacement->id]]);
        self::assertNull($auction->marketing_data['brochure_url']);
    }
}

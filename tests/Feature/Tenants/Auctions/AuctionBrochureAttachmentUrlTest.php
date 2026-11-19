<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionBrochureAttachmentUrlTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_replacement_clears_legacy_extra_brochures_only_for_the_current_auction(): void
    {
        Storage::fake('tenant_public');
        $auction = $this->cycleAuction();
        $other = $this->cycleAuction();
        foreach (['legacy-one.pdf', 'legacy-two.pdf'] as $path) {
            $auction->attachments()->create(['file_path' => $path, 'file_name' => $path, 'file_type' => 'pdf', 'category' => 'marketing', 'report_section' => 'brochure']);
        }
        $otherFile = $other->attachments()->create(['file_path' => 'other.pdf', 'file_name' => 'other.pdf', 'category' => 'marketing', 'report_section' => 'brochure']);
        $updated = app(UpdateAuctionAction::class)->execute($auction, [], brochureEvidence: [UploadedFile::fake()->create('single.pdf', 10, 'application/pdf')]);
        self::assertSame('single.pdf', $updated->attachments()->where('report_section', 'brochure')->sole()->file_name);
        self::assertNotNull($otherFile->fresh());
    }

    public function test_domain_action_rejects_multiple_brochure_uploads_before_changing_files(): void
    {
        $auction = $this->cycleAuction();
        $this->expectException(ValidationException::class);
        app(UpdateAuctionAction::class)->execute($auction, [], brochureEvidence: [UploadedFile::fake()->create('one.pdf'), UploadedFile::fake()->create('two.pdf')]);
    }

    public function test_brochure_input_accepts_one_file_and_rejects_a_file_array(): void
    {
        foreach ([new StoreAuctionRequest, new UpdateAuctionRequest] as $request) {
            $rules = array_filter($request->rules(), static fn (string $key): bool => str_starts_with($key, 'brochure_evidence'), ARRAY_FILTER_USE_KEY);
            self::assertFalse(Validator::make(['brochure_evidence' => UploadedFile::fake()->create('book.pdf', 10, 'application/pdf')], $rules)->fails());
            self::assertTrue(Validator::make(['brochure_evidence' => [UploadedFile::fake()->create('one.pdf', 10, 'application/pdf'), UploadedFile::fake()->create('two.pdf', 10, 'application/pdf')]], $rules)->fails());
        }
    }

    public function test_marketing_view_renders_a_single_brochure_file_input(): void
    {
        view()->share('errors', new ViewErrorBag);
        $html = view('tenant.auctions.partials.steps.step4-marketing', ['auction' => null, 'cityOptions' => []])->render();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $input = (new \DOMXPath($document))->query('//input[@id="brochure_evidence"]')->item(0);
        self::assertNotNull($input);
        self::assertSame('brochure_evidence', $input->getAttribute('name'));
        self::assertFalse($input->hasAttribute('multiple'));
    }

    public function test_manual_brochure_urls_are_excluded_from_validated_input(): void
    {
        foreach ([new StoreAuctionRequest, new UpdateAuctionRequest] as $request) {
            $rules = $request->rules();
            $validator = Validator::make(['marketing' => ['brochure_url' => 'manual input is ignored']], [
                'marketing' => $rules['marketing'], 'marketing.brochure_url' => $rules['marketing.brochure_url'],
            ]);
            self::assertFalse($validator->fails());
            self::assertArrayNotHasKey('brochure_url', $validator->validated()['marketing']);
        }
    }

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

        $auction = app(UpdateAuctionAction::class)->execute($auction, [], brochureEvidence: [UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf')]);
        $replacement = $auction->attachments()->where('report_section', 'brochure')->sole();
        self::assertNotSame($original->file_path, $replacement->file_path);
        self::assertSame(url(Storage::disk('tenant_public')->url($replacement->file_path)), $auction->marketing_data['brochure_url']);

        $auction = app(UpdateAuctionAction::class)->execute($auction, ['remove_brochure_evidence' => true]);
        self::assertNull($auction->marketing_data['brochure_url']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\AuctionEstablishments\ApproveAuctionEstablishmentRequestAction;
use App\Actions\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequestAction;
use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionEstablishmentWorkflowTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_save_preserves_sources_and_owner_relationships_and_queues_establishment_only(): void
    {
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        $property = $auction->properties()->first();
        $owner = $property->owners()->create(['owner_name' => 'Actual owner', 'national_id_or_cr' => '000111']);
        $input = $this->payload($auction);
        $input['data']['organization']['name'] = 'Forged organization';
        $input['data']['properties'][0]['owner_name'] = 'Forged owner';
        $request = app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, $input, null);
        self::assertSame($this->tenant->company_name, $request->data['organization']['name']);
        self::assertSame($this->tenant->commercial_number, $request->data['organization']['commercial_registration']);
        self::assertSame('Actual owner', $request->data['properties'][0]['owner_name']);
        self::assertSame($property->id, $request->data['properties'][0]['property_id']);
        self::assertSame($owner->id, $request->data['owner_relationships'][0]['owner_id']);
        self::assertSame('Actual owner', $owner->fresh()->owner_name);
        self::assertFalse($request->isApproved());
        self::assertSame('under_review', $request->status);
        $generation = AuctionFormGeneration::query()->where('auction_id', $auction->id)->sole();
        self::assertSame('establishment', $generation->type);
        self::assertSame('queued', $generation->status);
        self::assertSame($request->data['properties'], $generation->snapshot['properties']);
    }

    public function test_approval_freezes_data_and_rejects_resave_and_reapproval(): void
    {
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        $save = app(SaveAuctionEstablishmentRequestAction::class);
        $request = $save->execute($auction, $this->payload($auction), null);
        $snapshot = $request->data;
        $approved = app(ApproveAuctionEstablishmentRequestAction::class)->execute($auction, ' 000456 ', null);
        self::assertTrue($approved->isApproved());
        self::assertSame('000456', $approved->approval_number);
        self::assertSame($snapshot, $approved->data);
        try {
            $save->execute($auction, $this->payload($auction), null);
            self::fail('Approved request accepted edits.');
        } catch (ValidationException) {
            self::assertSame($snapshot, $approved->fresh()->data);
        }
        $this->expectException(ValidationException::class);
        app(ApproveAuctionEstablishmentRequestAction::class)->execute($auction, 'new number', null);
    }

    public function test_foreign_property_and_approval_metadata_are_rejected_without_writes(): void
    {
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        $payload = $this->payload($auction);
        $payload['data']['properties'][0]['property_id'] = $this->requestAuction()->properties()->first()->id;
        try {
            app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, $payload, null);
            self::fail('Foreign property accepted.');
        } catch (ValidationException) {
            self::assertFalse(AuctionEstablishmentRequest::query()->where('auction_id', $auction->id)->exists());
        }
        $payload = $this->payload($auction);
        $payload['approval_number'] = 'injected approval';
        $this->expectException(ValidationException::class);
        app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, $payload, null);
    }

    public function test_required_typed_documents_and_latest_property_set_are_enforced(): void
    {
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        $payload = $this->payload($auction);
        unset($payload['attachments']['deed']);
        try {
            app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, $payload, null);
            self::fail('Missing title document accepted.');
        } catch (ValidationException) {
            self::assertSame([], Storage::disk('tenant_private')->allFiles());
        }
        app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, $this->payload($auction), null);
        $auction->properties()->detach();
        $this->expectException(ValidationException::class);
        app(ApproveAuctionEstablishmentRequestAction::class)->execute($auction, '0001', null);
    }

    public function test_pending_request_can_be_updated_while_preserving_existing_documents(): void
    {
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        $save = app(SaveAuctionEstablishmentRequestAction::class);
        $original = $save->execute($auction, $this->payload($auction), null);
        $payload = $this->payload($auction);
        unset($payload['attachments']);
        $payload['data']['funds']['safekeeping'] = 'Updated dedicated client account';
        $updated = $save->execute($auction, $payload, null);
        self::assertSame($original->id, $updated->id);
        self::assertSame($original->data['attachments'], $updated->data['attachments']);
        self::assertSame('Updated dedicated client account', $updated->data['funds']['safekeeping']);
    }

    public function test_http_form_save_approval_and_private_document_download_follow_tenant_permissions(): void
    {
        $this->withoutMiddleware([CheckSubscription::class, EnsureUserHasActiveLicense::class]);
        // Avoid a central-context reconnect in the layout discarding transactional fixtures.
        cache()->put('tenant:'.tenant('id').':subscription', false, now()->addMinute());
        Storage::fake('tenant_private');
        $user = User::query()->where('email', 'demo@lot.com')->firstOrFail();
        foreach (['view', 'create', 'edit', 'approve'] as $action) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => 'auction_establishment_requests.'.$action, 'guard_name' => 'tenant']));
        }
        $this->actingAs($user, 'tenant');
        $auction = $this->requestAuction();
        $this->get(tenant_route_name('auction-establishment-requests.index'))->assertOk()->assertSee('طلبات إقامة المزادات');
        $edit = tenant_route_name('auction-establishment-requests.edit', $auction);
        $approve = tenant_route_name('auction-establishment-requests.approve', $auction);
        $details = tenant_route_name('auctions.show', $auction);
        $attachment = tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => 'manager_certificate']);
        $editResponse = $this->get($edit)->assertOk()->assertSee('data[organization][unified_number]', false)->assertSee('data[properties][0][contract_number]', false)->assertSee('attachments[manager_certificate]', false);
        $this->saveUiProof('establishment-stepper', $editResponse->getContent());
        $this->put(tenant_route_name('auction-establishment-requests.update', $auction), $this->payload($auction))->assertSessionHasNoErrors()->assertRedirect($details);
        $this->getJson(tenant_route_name('auction-establishment-requests.index').'?draw=1&length=100', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['data' => [['request_status', 'approval_number', 'submitted_at', 'actions']]]);
        $user->revokePermissionTo('auctions.view');
        $detailsResponse = $this->get($details)->assertOk()->assertSee('data-regulatory-form', false)->assertSee('approval_number', false);
        $this->saveUiProof('pending-request-details', $detailsResponse->getContent());
        $this->get(tenant_route_name('auctions.index'))->assertForbidden();
        $this->get($attachment)->assertOk()->assertDownload('manager.pdf');
        $this->post($approve, ['approval_number' => '000900'])->assertSessionHasNoErrors()->assertRedirect($details);
        self::assertTrue($auction->fresh()->establishmentRequest->isApproved());
        $this->saveUiProof('approved-request-details', $this->get($details)->assertOk()->getContent());
        $this->putJson(tenant_route_name('auction-establishment-requests.update', $auction), $this->payload($auction))->assertForbidden();
        $this->postJson(tenant_route_name('wizard-drafts.store'), $this->payload($auction) + [
            'resource_key' => 'tenant.auction-establishment-request.'.$auction->id,
            'step' => 5,
        ])->assertForbidden();
        $this->get($edit)->assertRedirect();
        $user->revokePermissionTo('auction_establishment_requests.approve');
        $this->post($approve, ['approval_number' => '000901'])->assertForbidden();
        $user->revokePermissionTo('auction_establishment_requests.view');
        $this->get($attachment)->assertForbidden();
    }

    public function test_request_missing_source_fields_can_be_completed_and_updated_before_approval(): void
    {
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        $auction->update(['platform_name' => null]);
        $payload = $this->payload($auction);
        $payload['data']['auction']['platform'] = 'Collected licensed platform';
        $save = app(SaveAuctionEstablishmentRequestAction::class);
        $request = $save->execute($auction, $payload, null);
        self::assertSame('Collected licensed platform', $request->data['auction']['platform']);
        $payload['data']['auction']['platform'] = 'Corrected licensed platform';
        $payload['data']['properties'][0]['deed_number'] = '000999';
        unset($payload['attachments']);
        $request = $save->execute($auction, $payload, null);
        self::assertSame('Corrected licensed platform', $request->data['auction']['platform']);
        self::assertSame('000999', $request->data['properties'][0]['deed_number']);
    }

    public function test_stepper_draft_uploads_are_validated_restored_and_discarded_after_final_save(): void
    {
        $this->withoutMiddleware([CheckSubscription::class, EnsureUserHasActiveLicense::class]);
        Storage::fake('tenant_private');
        Storage::fake('local');
        $user = User::query()->where('email', 'demo@lot.com')->firstOrFail();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'auction_establishment_requests.create', 'guard_name' => 'tenant']));
        $this->actingAs($user, 'tenant');
        $auction = $this->requestAuction();
        $key = 'tenant.auction-establishment-request.'.$auction->id;
        $target = parse_url(tenant_route_name('auction-establishment-requests.update', $auction), PHP_URL_PATH);
        $payload = $this->payload($auction);
        $draft = $this->postJson(tenant_route_name('wizard-drafts.store'), $payload + [
            'resource_key' => $key, 'step' => 5, 'target_path' => $target,
        ])->assertOk()->json();
        $other = $this->requestAuction();
        $this->putJson(tenant_route_name('auction-establishment-requests.update', $other), [
            '_wizard_draft_id' => $draft['draft_id'], '_wizard_resource_key' => $key,
        ])->assertForbidden();
        $this->postJson(tenant_route_name('wizard-drafts.validate-step'), [
            'target_path' => $target, 'target_method' => 'PUT', 'fields' => ['data.organization'],
            '_wizard_draft_id' => $draft['draft_id'], '_wizard_resource_key' => $key,
        ])->assertOk()->assertJsonPath('success', true);
        $this->put(tenant_route_name('auction-establishment-requests.update', $auction), [
            '_wizard_draft_id' => $draft['draft_id'], '_wizard_resource_key' => $key,
        ])->assertSessionHasNoErrors()->assertRedirect(tenant_route_name('auctions.show', $auction));
        $request = $auction->fresh()->establishmentRequest;
        self::assertNotNull($request);
        Storage::disk('tenant_private')->assertExists($request->data['attachments']['manager_certificate'][0]['path']);
        self::assertFalse(\App\Models\Shared\WizardDraft::on('tenant')->whereKey($draft['draft_id'])->exists());
    }

    private function requestAuction(): Auction
    {
        $auction = $this->cycleAuction();
        $auction->update(['platform_name' => 'Licensed platform', 'platform_url' => 'https://example.com', 'start_time' => '16:00']);

        return $auction->fresh();
    }

    private function saveUiProof(string $name, string $html): void
    {
        if (getenv('AUCTION_ESTABLISHMENT_UI_PROOF') !== '1') {
            return;
        }
        $directory = base_path('tmp/auction-establishment-ui');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
        file_put_contents($directory.'/'.$name.'.html', $html);
    }

    public function test_real_browser_generates_native_pdfs_from_saved_request_and_closing(): void
    {
        if (getenv('AUCTION_REGULATORY_RENDER_SMOKE') !== '1') {
            self::markTestSkipped('Enable AUCTION_REGULATORY_RENDER_SMOKE for the real Chromium PDF integration check.');
        }
        Storage::fake('tenant_private');
        $auction = $this->requestAuction();
        app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, $this->payload($auction), null);
        app(ApproveAuctionEstablishmentRequestAction::class)->execute($auction, '000900', null);
        $propertyId = $auction->properties()->first()->id;
        app(\App\Actions\Tenants\AuctionClosings\SaveAuctionClosingAction::class)->execute($auction, [
            'closing_date' => now()->toDateString(),
            'results' => [$propertyId => ['status' => 'sold', 'buyer_name' => 'مشتري اختبار التوليد', 'sale_price' => '1350000.25']],
        ], null);
        $directory = base_path('tmp/pdfs/auction-workflow');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
        foreach (['establishment', 'closing'] as $type) {
            $generation = app(\App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService::class)->latest($auction, $type);
            self::assertNotNull($generation);
            (new \App\Jobs\Tenants\Auctions\GenerateAuctionRegulatoryFormJob(
                (string) tenant('id'), $auction->id, $generation->id, $generation->revision_token,
            ))->handle(app(\App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms::class), app(\App\Services\Tenants\Auctions\BrowsershotDocumentRenderer::class));
            self::assertSame('ready', $generation->fresh()->status);
            $bytes = Storage::disk('tenant_private')->get($generation->fresh()->file_path);
            self::assertStringStartsWith('%PDF-', $bytes);
            self::assertStringContainsString('%%EOF', $bytes);
            file_put_contents($directory.'/'.$type.'.pdf', $bytes);
        }
    }

    private function payload(Auction $auction): array
    {
        $data = json_decode(file_get_contents(resource_path('auction-regulatory-forms/example.json')), true, flags: JSON_THROW_ON_ERROR);
        $property = $auction->properties()->first();
        $row = array_replace($data['property'], $data['properties'][0], ['property_id' => $property->id, 'age' => '0', 'opening_value' => '1000000.00', 'entry_amount' => '0.00', 'estimated_value' => '1200000.00', 'agent_name' => '', 'agency_number' => '']);
        $data['properties'] = [$row];
        unset($data['property'], $data['agents'], $data['closing'], $data['submission_date']);

        return ['data' => $data, 'declaration_accepted' => '1', 'attachments' => [
            'manager_certificate' => UploadedFile::fake()->createWithContent('manager.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"),
            'auctioneer_certificate' => UploadedFile::fake()->createWithContent('auctioneer.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"),
            'deed' => [$property->id => UploadedFile::fake()->createWithContent('deed.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF")],
        ]];
    }
}

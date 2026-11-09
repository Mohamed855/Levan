<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Controllers\Tenants\Auctions\AuctionRegulatoryFormsController;
use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionRegulatoryFormsAccessTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    private function allow(string $permission): void
    {
        $user = User::where('email', 'demo@lot.com')->firstOrFail();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'tenant']));
        $this->actingAs($user, 'tenant');
    }

    public function test_auction_view_permission_does_not_grant_request_document_access(): void
    {
        $this->actingAs(User::where('email', 'demo@lot.com')->firstOrFail(), 'tenant');
        $this->expectException(AuthorizationException::class);
        app(AuctionRegulatoryFormsController::class)->status($this->cycleAuction(), 'establishment');
    }

    public function test_viewing_request_document_does_not_grant_regeneration(): void
    {
        $this->allow('auction_establishment_requests.view');
        $this->expectException(AuthorizationException::class);
        app(AuctionRegulatoryFormsController::class)->retry($this->cycleAuction(), 'establishment');
    }

    public function test_closing_document_is_unavailable_for_unapproved_saved_request(): void
    {
        $this->allow('auction_closings.view');
        $auction = $this->cycleAuction();
        AuctionEstablishmentRequest::create(['auction_id' => $auction->id, 'data' => ['auction' => ['name' => 'Pending']], 'submitted_at' => now()]);
        AuctionClosing::create(['auction_id' => $auction->id, 'closing_date' => now(), 'saved_at' => now(), 'approval_number' => 'LEGACY']);
        try {
            app(AuctionRegulatoryFormsController::class)->status($auction->fresh(), 'closing');
            self::fail('A historical closing must not grant access to closing forms before request approval.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_download_uses_last_good_file_even_when_new_generation_failed(): void
    {
        $this->allow('auction_establishment_requests.view');
        Storage::fake('tenant_private');
        $auction = $this->cycleAuction();
        AuctionEstablishmentRequest::create(['auction_id' => $auction->id, 'data' => ['auction' => ['name' => 'Saved']], 'submitted_at' => now()]);
        AuctionFormGeneration::create([
            'auction_id' => $auction->id, 'type' => 'establishment', 'revision_token' => (string) Str::uuid(),
            'status' => 'ready', 'progress' => 100, 'snapshot' => [], 'file_path' => 'good.pdf',
        ]);
        AuctionFormGeneration::create([
            'auction_id' => $auction->id, 'type' => 'establishment', 'revision_token' => (string) Str::uuid(),
            'status' => 'failed', 'snapshot' => [], 'error_message' => 'Private failure details',
        ]);
        Storage::disk('tenant_private')->put('good.pdf', 'Last good PDF');
        $response = app(AuctionRegulatoryFormsController::class)->download($auction->fresh(), 'establishment');
        ob_start();
        $response->sendContent();
        self::assertSame('Last good PDF', ob_get_clean());
        self::assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
        $status = app(AuctionRegulatoryFormsController::class)->status($auction->fresh(), 'establishment')->getData(true);
        self::assertSame('failed', $status['status']);
        self::assertTrue($status['has_file']);
        self::assertStringNotContainsString('Private failure details', $status['error_message']);
    }

    public function test_details_boxes_are_hidden_before_request_save_and_show_only_qualifying_forms(): void
    {
        $this->allow('auction_establishment_requests.view');
        $this->allow('auction_closings.view');
        $auction = $this->cycleAuction();
        $empty = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction])->render();
        self::assertStringNotContainsString('data-regulatory-form="establishment"', $empty);
        self::assertStringNotContainsString('data-regulatory-form="closing"', $empty);
        $request = AuctionEstablishmentRequest::create(['auction_id' => $auction->id, 'data' => ['auction' => ['name' => 'Saved']], 'submitted_at' => now()]);
        $pending = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction->fresh()])->render();
        self::assertStringContainsString('data-regulatory-form="establishment"', $pending);
        self::assertStringNotContainsString('data-regulatory-form="closing"', $pending);
        $request->forceFill(['approved_at' => now(), 'approval_number' => '000187'])->save();
        AuctionClosing::create(['auction_id' => $auction->id, 'closing_date' => now(), 'saved_at' => now()]);
        $approved = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction->fresh()])->render();
        self::assertStringContainsString('data-regulatory-form="establishment"', $approved);
        self::assertStringContainsString('data-regulatory-form="closing"', $approved);
    }

    public function test_both_forms_can_be_manually_regenerated_after_all_properties_are_sold(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Cache::put('auction_reports_worker_spawn_lock', 1, 60);
        foreach (['auction_establishment_requests.regenerate', 'auction_closings.regenerate'] as $permission) {
            $this->allow($permission);
        }
        $auction = $this->cycleAuction();
        $propertyId = $auction->properties()->first()->id;
        $data = json_decode(file_get_contents(resource_path('auction-regulatory-forms/example.json')), true, flags: JSON_THROW_ON_ERROR);
        $data['properties'] = [array_replace($data['properties'][0], ['property_id' => $propertyId])];
        $request = AuctionEstablishmentRequest::create(['auction_id' => $auction->id, 'data' => $data, 'submitted_at' => now()]);
        $request->forceFill(['approved_at' => now(), 'approval_number' => '000187'])->save();
        AuctionClosing::create(['auction_id' => $auction->id, 'closing_date' => now(), 'saved_at' => now()]);
        $auction->properties()->updateExistingPivot($propertyId, ['result_status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => '1350000.25']);
        self::assertTrue($auction->fresh()->isClosed());
        foreach (['establishment', 'closing'] as $type) {
            $response = app(AuctionRegulatoryFormsController::class)->retry($auction->fresh(), $type);
            self::assertSame(202, $response->getStatusCode());
            self::assertSame('queued', $response->getData(true)['status']);
            self::assertSame(5, $response->getData(true)['progress']);
        }
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\Tenants\Auctions\GenerateAuctionRegulatoryFormJob::class, 2);
    }
}

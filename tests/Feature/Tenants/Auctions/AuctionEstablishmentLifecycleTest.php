<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\AuctionClosings\SaveAuctionClosingAction;
use App\Actions\Tenants\MarketingCampaigns\DeleteMarketingCampaignAction;
use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionEstablishmentLifecycleTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_scopes_match_automatic_approval_and_nonempty_actual_property_results(): void
    {
        $auction = $this->cycleAuction();
        $auction->update(['is_active' => false, 'end_date' => '2020-01-01']);
        self::assertSame('pending', $auction->fresh()->status->value);
        self::assertTrue(Auction::pending()->whereKey($auction)->exists());
        $request = $this->approve($auction);
        self::assertSame('approved', $auction->fresh()->status->value);
        self::assertTrue(Auction::approved()->whereKey($auction)->exists());
        $property = $auction->properties()->first();
        $auction->properties()->updateExistingPivot($property->id, ['result_status' => 'sold']);
        self::assertSame('closed', $auction->fresh()->status->value);
        self::assertTrue(Auction::closed()->whereKey($auction)->exists());
        self::assertFalse(Auction::open()->whereKey($auction)->exists());
        $request->forceFill(['approval_number' => '  '])->save();
        self::assertSame('closed', $auction->fresh()->status->value);
        self::assertTrue(Auction::closed()->whereKey($auction)->exists());
        $request->forceFill(['approval_number' => '000187'])->save();
        $auction->properties()->detach();
        self::assertSame('approved', $auction->fresh()->status->value);
        self::assertTrue(Auction::approved()->whereKey($auction)->exists());
    }

    public function test_closing_rejects_pending_request_even_with_forged_approval_number(): void
    {
        $auction = $this->cycleAuction();
        $this->expectException(ValidationException::class);
        app(SaveAuctionClosingAction::class)->execute($auction, ['approval_number' => 'FORGED', 'closing_date' => '2026-11-09', 'results' => []], null);
    }

    public function test_historical_finalized_snapshot_remains_locked_even_with_malformed_results(): void
    {
        $auction = $this->cycleAuction();
        $auction->closing()->create(['closing_date' => now()->toDateString(), 'saved_at' => now(), 'finalized_snapshot' => ['properties' => []]]);
        $this->expectException(ValidationException::class);
        app(AuctionReportLock::class)->assertEditable($auction);
    }

    public function test_closing_uses_request_approval_and_refreshes_reports_and_closing_form(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $this->approve($auction);
        $property = $auction->properties()->first();
        $closing = app(SaveAuctionClosingAction::class)->execute($auction, ['approval_number' => 'FORGED', 'closing_date' => '2026-11-09', 'results' => [$property->id => ['status' => 'unsold', 'unsold_reason' => 'No bids']]], null);
        self::assertSame('000187', $closing->approval_number);
        self::assertNotNull($auction->latestReportGeneration()->first());
        self::assertDatabaseHas('auction_form_generations', ['auction_id' => $auction->id, 'type' => 'closing']);
        self::assertDatabaseMissing('auction_form_generations', ['auction_id' => $auction->id, 'type' => 'establishment']);
    }

    public function test_closed_auction_rejects_all_campaign_paths_and_auction_mutations(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $save = app(SaveMarketingCampaignAction::class);
        $campaign = $save->execute(null, ['auction_id' => $auction->id, 'channel' => 'google', 'metrics' => ['views' => 0]], null);
        $this->approve($auction);
        $property = $auction->properties()->first();
        $auction->properties()->updateExistingPivot($property->id, ['result_status' => 'sold']);
        $operations = [
            fn () => $save->execute(null, ['auction_id' => $auction->id, 'channel' => 'google', 'metrics' => ['views' => 1]], null),
            fn () => $save->execute($campaign, ['auction_id' => $auction->id, 'channel' => 'google', 'metrics' => ['views' => 1]], null),
            fn () => $save->executeBatch($auction, ['channels' => ['google'], 'campaigns' => ['google' => ['metrics' => ['views' => 1]]]], null),
            fn () => app(DeleteMarketingCampaignAction::class)->execute($campaign),
            fn () => app(DeleteMarketingCampaignAction::class)->executeForAuction($auction),
            fn () => app(AuctionService::class)->deleteAuction($auction),
            fn () => app(AuctionService::class)->toggleStatus($auction),
            fn () => app(AuctionReportLock::class)->assertClosingAllowed($auction),
        ];
        foreach ($operations as $operation) {
            try {
                $operation();
                self::fail('Closed auction mutation accepted.');
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
        self::assertSame(0, $campaign->fresh()->metrics['views']);
        self::assertNotNull($auction->fresh());
    }

    public function test_approved_request_prevents_changing_auction_property_membership(): void
    {
        $auction = $this->cycleAuction();
        $this->approve($auction);
        $propertyId = $auction->properties()->first()->id;
        try {
            app(\App\Actions\Tenants\Auctions\UpdateAuctionAction::class)->execute($auction, ['property_ids' => []]);
            self::fail('Approved property membership changed.');
        } catch (ValidationException) {
            self::assertSame([$propertyId], $auction->fresh()->properties->modelKeys());
        }
    }

    public function test_closing_rejects_a_stale_approved_property_set_before_writing_results(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $this->approve($auction);
        $other = $this->cycleAuction();
        $foreignId = $other->properties()->first()->id;
        $auction->properties()->sync([$foreignId => ['result_status' => 'pending']]);
        try {
            app(SaveAuctionClosingAction::class)->execute($auction, ['closing_date' => '2026-11-09', 'results' => [
                $foreignId => ['status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => 200],
            ]], null);
            self::fail('Closing accepted a stale approval.');
        } catch (ValidationException) {
            self::assertNull($auction->fresh()->closing);
            self::assertSame('pending', $auction->fresh()->properties->first()->pivot->result_status);
        }
    }

    private function approve(Auction $auction): AuctionEstablishmentRequest
    {
        $request = AuctionEstablishmentRequest::create(['auction_id' => $auction->id, 'data' => ['properties' => $auction->properties->map(fn ($property) => ['property_id' => $property->id])->all()], 'submitted_at' => now()]);
        $request->forceFill(['approved_at' => now(), 'approval_number' => '000187'])->save();

        return $request;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\AuctionClosings\SaveAuctionClosingAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Actions\Tenants\MarketingCampaigns\DeleteMarketingCampaignAction;
use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Actions\Tenants\MassDelete\DeleteTenantRecordsAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Services\Tenants\Auctions\AuctionService;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportCycleMutationsTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_changing_campaign_type_reclassifies_its_saved_evidence(): void
    {
        Queue::fake();
        Storage::fake('tenant_public');
        $auction = $this->cycleAuction();
        $save = app(SaveMarketingCampaignAction::class);
        $campaign = $save->execute(null, ['auction_id' => $auction->id, 'channel' => 'other', 'platform_label' => 'Other channel', 'metrics' => ['views' => 1]], null, [UploadedFile::fake()->image('proof.png')]);
        $path = $campaign->evidence()->first()->file_path;
        $updated = $save->execute($campaign, ['auction_id' => $auction->id, 'channel' => 'youtube', 'metrics' => ['views' => 2]], null);
        self::assertNull($updated->platform_label);
        self::assertSame('video', $updated->evidence()->first()->report_section);
        self::assertSame('youtube', $updated->evidence()->first()->report_metadata['channel']);
        Storage::disk('tenant_public')->assertExists($path);
    }

    public function test_legacy_whatsapp_date_upgrade_preserves_the_saved_campaign_date(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $auction->update(['marketing_data' => ['whatsapp_date' => '2026-11-01']]);
        $campaign = app(SaveMarketingCampaignAction::class)->execute(null, ['auction_id' => $auction->id, 'channel' => 'whatsapp', 'metrics' => ['messages' => 0]], null);
        $migration = require database_path('migrations/tenant/2026_11_05_120500_add_marketing_campaign_date.php');
        $migration->up();
        self::assertSame('2026-11-01', $campaign->fresh()->campaign_date->format('Y-m-d'));
    }

    public function test_closing_rejects_foreign_and_empty_property_sets_and_keeps_unsold_editable(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $other = $this->cycleAuction();
        $property = $auction->properties()->first();
        $foreign = $other->properties()->first();
        $action = app(SaveAuctionClosingAction::class);
        try {
            $action->execute($auction, ['closing_date' => now()->toDateString(), 'results' => [$foreign->id => ['status' => 'unsold', 'unsold_reason' => 'Reason']]], null);
            self::fail('Foreign property accepted');
        } catch (ValidationException) {
            self::assertNull($auction->fresh()->closing);
        }
        $closing = $action->execute($auction, ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'unsold', 'unsold_reason' => 'No sale']]], null);
        self::assertNull($closing->finalized_snapshot);
        self::assertSame('No sale', $auction->fresh()->properties()->first()->pivot->unsold_reason);
        app(AuctionReportLock::class)->assertEditable($auction->fresh());
        $other->properties()->detach();
        $this->expectException(ValidationException::class);
        $action->execute($other->fresh(), ['closing_date' => now()->toDateString(), 'results' => []], null);
    }

    public function test_finalized_campaigns_and_bulk_auction_deletion_are_rejected(): void
    {
        Queue::fake();
        $open = $this->cycleAuction();
        $final = $this->cycleAuction();
        $save = app(SaveMarketingCampaignAction::class);
        $campaign = $save->execute(null, ['auction_id' => $final->id, 'channel' => 'x', 'metrics' => ['views' => 0]], null);
        $property = $final->properties()->first();
        app(SaveAuctionClosingAction::class)->execute($final, ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => 500]]], null);
        foreach (['campaign-update', 'campaign-delete', 'bulk-delete'] as $operation) {
            try {
                match ($operation) {
                    'campaign-update' => $save->execute($campaign, ['auction_id' => $open->id, 'channel' => 'x', 'metrics' => ['views' => 1]], null),
                    'campaign-delete' => app(DeleteMarketingCampaignAction::class)->execute($campaign),
                    'bulk-delete' => app(DeleteTenantRecordsAction::class)->execute(collect([$open, $final])),
                };
                self::fail('Finalized mutation allowed: '.$operation);
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
        self::assertFalse($open->fresh()->trashed());
        self::assertSame($final->id, $campaign->fresh()->auction_id);
    }

    public function test_shared_property_updates_refresh_only_open_auctions_and_settings_wait_for_manual_regeneration(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $property = $auction->properties()->first();
        $service = app(AuctionReportService::class);
        $service->requestGeneration($auction, (string) tenant('id'));
        app(UpdatePropertyAction::class)->execute($property, ['name' => 'Changed property']);
        self::assertSame('Changed property', $auction->latestReportGeneration()->first()->snapshot['properties'][0]['name']);
        $count = $auction->reportGenerations()->count();
        app(UpdateTenantSettingsAction::class)->execute(['office_name' => 'Company', 'report_phone' => '0500000001']);
        self::assertSame($count, $auction->reportGenerations()->count());
        self::assertSame('0500000001', $service->retryLatest($auction, (string) tenant('id'))->snapshot['marketing']['contacts']['phone']);
        app(SaveAuctionClosingAction::class)->execute($auction, ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => 500]]], null);
        $count = $auction->reportGenerations()->count();
        app(UpdatePropertyAction::class)->execute($property->fresh(), ['name' => 'Changed after final']);
        self::assertSame($count, $auction->reportGenerations()->count());
        app(UpdateTenantSettingsAction::class)->execute(['office_name' => 'Company', 'report_phone' => '0500000002']);
        $manual = $service->retryLatest($auction, (string) tenant('id'));
        self::assertSame('Changed property', $manual->snapshot['properties'][0]['name']);
        self::assertSame('0500000002', $manual->snapshot['marketing']['contacts']['phone']);
    }

    public function test_campaign_reassignment_refreshes_both_auctions(): void
    {
        Queue::fake();
        $first = $this->cycleAuction();
        $second = $this->cycleAuction();
        $save = app(SaveMarketingCampaignAction::class);
        $campaign = $save->execute(null, ['auction_id' => $first->id, 'channel' => 'x', 'metrics' => ['views' => 1]], null);
        $save->execute($campaign, ['auction_id' => $second->id, 'channel' => 'x', 'metrics' => ['views' => 2]], null);
        self::assertSame([], $first->latestReportGeneration()->first()->snapshot['marketing']['campaigns']);
        self::assertSame(2, $second->latestReportGeneration()->first()->snapshot['marketing']['campaigns'][0]['views']);
    }

    public function test_sync_connection_still_dispatches_outside_testing_environment(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        config(['auction-renderer.queue_connection' => 'sync']);
        $this->app['env'] = 'local';
        app(AuctionReportService::class)->requestGeneration($auction, (string) tenant('id'));
        Queue::assertPushed(GenerateAuctionReportsJob::class);
    }

    public function test_mutation_snapshot_sources_are_read_after_auction_lock(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        app(AuctionReportRefreshService::class)->afterMutation([$auction->id]);
        $lock = array_find_key($queries, fn ($sql) => str_contains($sql, 'for update') && str_contains($sql, '`auctions`'));
        $campaigns = array_find_key($queries, fn ($sql) => str_contains($sql, 'select') && str_contains($sql, '`auction_marketing_campaigns`'));
        self::assertNotNull($lock);
        self::assertNotNull($campaigns);
        self::assertLessThan($campaigns, $lock);
    }

    public function test_auction_edit_preserves_existing_closing_and_legacy_marketing_data(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $property = $auction->properties()->first();
        $auction->update(['marketing_data' => ['campaigns' => [['platform' => 'x', 'views' => 8]], 'introduction' => 'Old']]);
        $auction->properties()->updateExistingPivot($property->id, ['result_status' => 'unsold', 'unsold_reason' => 'Reason', 'result_notes' => 'Notes', 'buyer_name' => null]);
        app(UpdateAuctionAction::class)->execute($auction, ['name' => 'Updated', 'marketing' => ['introduction' => 'New']]);
        self::assertSame('unsold', $auction->fresh()->properties()->first()->pivot->result_status);
        self::assertSame('Reason', $auction->fresh()->properties()->first()->pivot->unsold_reason);
        self::assertSame('Notes', $auction->fresh()->properties()->first()->pivot->result_notes);
        self::assertSame(8, $auction->fresh()->marketing_data['campaigns'][0]['views']);
    }

    public function test_finalized_auction_rejects_business_mutations(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $property = $auction->properties()->first();
        app(SaveAuctionClosingAction::class)->execute($auction, ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => 500]]], null);
        foreach (['update', 'toggle', 'delete'] as $operation) {
            try {
                match ($operation) {
                    'update' => app(UpdateAuctionAction::class)->execute($auction->fresh(), ['name' => 'Changed']),
                    'toggle' => app(AuctionService::class)->toggleStatus($auction->fresh()),
                    'delete' => app(AuctionService::class)->deleteAuction($auction->fresh()),
                };
                self::fail('Finalized auction allowed '.$operation);
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_campaign_create_edit_delete_refreshes_and_preserves_zero(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $save = app(SaveMarketingCampaignAction::class);
        $campaign = $save->execute(null, ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]], null);
        self::assertSame(0, $campaign->metrics['views']);
        self::assertSame(1, $auction->reportGenerations()->count());
        $save->execute($campaign, ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 7]], null);
        self::assertSame(7, $auction->latestReportGeneration()->first()->snapshot['marketing']['campaigns'][0]['views']);
        app(DeleteMarketingCampaignAction::class)->execute($campaign->fresh());
        self::assertSame([], $auction->latestReportGeneration()->first()->snapshot['marketing']['campaigns']);
    }

    public function test_all_sold_closing_freezes_business_data_but_allows_regeneration(): void
    {
        Queue::fake();
        $auction = $this->cycleAuction();
        $property = $auction->properties()->first();
        $closing = app(SaveAuctionClosingAction::class)->execute($auction, ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'sold', 'buyer_name' => 'المشتري', 'sale_price' => 500]]], null);
        self::assertNotNull($closing->finalized_snapshot);
        $property->update(['name' => 'اسم جديد خارج اللقطة']);
        $generation = app(AuctionReportService::class)->retryLatest($auction->fresh(), (string) tenant('id'));
        self::assertSame('أرض', $generation->snapshot['properties'][0]['name']);
        $this->expectException(ValidationException::class);
        app(AuctionReportLock::class)->assertEditable($auction->fresh());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportDeferredStartTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_saving_only_records_the_generation_even_with_sync_configured(): void
    {
        Queue::fake();
        config(['auction-renderer.queue_connection' => 'sync']);
        $generation = app(AuctionReportService::class)->requestGeneration($this->cycleAuction(), (string) tenant('id'));
        self::assertSame('queued', $generation->status);
        self::assertSame(0, $generation->progress);
        Queue::assertNothingPushed();
    }

    public function test_details_status_starts_only_the_latest_generation_once_on_a_background_queue(): void
    {
        Queue::fake();
        config(['auction-renderer.queue_connection' => 'sync']);
        $auction = $this->cycleAuction();
        $service = app(AuctionReportService::class);
        $previous = $service->requestGeneration($auction, (string) tenant('id'));
        $latest = $service->requestGeneration($auction, (string) tenant('id'));
        $service->startPendingGeneration($auction, (string) tenant('id'));
        $service->startPendingGeneration($auction, (string) tenant('id'));
        Queue::assertPushedOn('auction-reports', GenerateAuctionReportsJob::class);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 1);
        Queue::assertPushed(GenerateAuctionReportsJob::class, fn ($job) => $job->connection === 'auction_reports');
        self::assertSame(0, $previous->fresh()->progress);
        self::assertSame(5, $latest->fresh()->progress);
    }

    public function test_automatic_saves_redirect_before_details_polling_starts_the_reports(): void
    {
        Queue::fake();
        config(['auction-renderer.queue_connection' => 'sync']);
        if (! getenv('AUCTION_REPORT_UI_PROOF')) {
            $this->withoutVite();
        }
        $this->withoutMiddleware(CheckSubscription::class);
        cache()->put('tenant:'.tenant('id').':subscription', (object) ['status' => null], 60);
        $user = User::where('email', 'demo@lot.com')->firstOrFail();
        foreach (['marketing_campaigns.create', 'auction_closings.create'] as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'tenant']));
        }
        $this->actingAs($user, 'tenant');
        $auction = $this->cycleAuction();
        $details = tenant_route_name('auctions.show', $auction);
        $this->post(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 1]])->assertRedirect($details);
        self::assertSame(0, $auction->latestReportGeneration()->first()->progress);
        Queue::assertNothingPushed();
        $property = $auction->properties()->first();
        $this->put(tenant_route_name('auction-closings.update', $auction), ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'unsold', 'unsold_reason' => 'لم يصل للسعر']]])->assertRedirect($details);
        $latest = $auction->latestReportGeneration()->first();
        self::assertSame(0, $latest->progress);
        Queue::assertNothingPushed();
        $response = $this->get($details)->assertOk()->assertSee('reports-progress-bar', false)->assertSee('startPolling(AUTO_TIMEOUT_MS);', false)->assertDontSee('startReportGeneration();', false);
        if (getenv('AUCTION_REPORT_UI_PROOF')) {
            file_put_contents(base_path('output/cycle-review/deferred-report-details.html'), $response->getContent());
        }
        Queue::assertNothingPushed();
        $status = tenant_route_name('auctions.reports.status', $auction);
        $this->getJson($status)->assertOk()->assertJsonPath('generation_id', $latest->id)->assertJsonPath('status', 'queued')->assertJsonPath('progress', 5);
        $this->getJson($status)->assertOk();
        Queue::assertPushed(GenerateAuctionReportsJob::class, 1);
        self::assertSame(2, $auction->reportGenerations()->count());
    }

    public function test_batch_campaign_save_update_and_ajax_delete_keep_generation_deferred(): void
    {
        Queue::fake();
        config(['auction-renderer.queue_connection' => 'sync']);
        $this->withoutMiddleware(CheckSubscription::class);
        cache()->put('tenant:'.tenant('id').':subscription', (object) ['status' => null], 60);
        $user = User::where('email', 'demo@lot.com')->firstOrFail();
        foreach (['marketing_campaigns.create', 'marketing_campaigns.edit', 'marketing_campaigns.delete'] as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'tenant']));
        }
        $this->actingAs($user, 'tenant');
        $auction = $this->cycleAuction();
        $payload = ['auction_id' => $auction->id, 'channels' => ['x', 'whatsapp'], 'campaigns' => ['x' => ['metrics' => ['views' => 0]], 'whatsapp' => ['metrics' => ['messages' => 23]]]];
        $details = tenant_route_name('auctions.show', $auction);
        $status = tenant_route_name('auctions.reports.status', $auction);
        $this->post(tenant_route_name('marketing-campaigns.store'), $payload)->assertRedirect($details);
        self::assertSame(2, $auction->marketingCampaigns()->count());
        self::assertSame(1, $auction->reportGenerations()->count());
        Queue::assertNothingPushed();
        $this->getJson($status)->assertOk()->assertJsonPath('progress', 5);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 1);

        $payload['campaigns']['x']['metrics']['views'] = 9;
        $this->put(tenant_route_name('marketing-campaigns.update', $auction->id), $payload)->assertRedirect($details);
        self::assertSame(2, $auction->reportGenerations()->count());
        self::assertSame(0, $auction->latestReportGeneration()->first()->progress);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 1);
        $this->getJson($status)->assertOk()->assertJsonPath('progress', 5);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 2);

        $this->deleteJson(tenant_route_name('marketing-campaigns.destroy', $auction->id))->assertOk()->assertJsonPath('success', true);
        self::assertSame(0, $auction->marketingCampaigns()->count());
        self::assertSame(3, $auction->reportGenerations()->count());
        self::assertSame(0, $auction->latestReportGeneration()->first()->progress);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 2);
        $this->getJson($status)->assertOk()->assertJsonPath('progress', 5);
        Queue::assertPushed(GenerateAuctionReportsJob::class, 3);
    }
}

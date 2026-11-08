<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportCycleHttpTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_campaign_http_flow_and_indexes_use_existing_tenant_access(): void
    {
        $this->withoutMiddleware(CheckSubscription::class);
        $this->withoutVite();
        Queue::fake();
        $auction = $this->cycleAuction();
        $user = User::where('email', 'demo@lot.com')->first();
        foreach (['marketing_campaigns.view', 'marketing_campaigns.create', 'marketing_campaigns.edit', 'marketing_campaigns.delete', 'auction_closings.view', 'auction_closings.create', 'auction_closings.edit', 'auction_reports.view'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'tenant']));
        }
        $this->actingAs($user, 'tenant');
        $this->withoutExceptionHandling();
        cache()->put('tenant:'.tenant('id').':subscription', (object) ['status' => null], 60);
        self::assertTrue($user->can('marketing_campaigns.create'), 'permission before index');
        $this->get(tenant_route_name('marketing-campaigns.index'))->assertOk();
        self::assertTrue($user->can('marketing_campaigns.create'), 'permission after index');
        $form = $this->get(tenant_route_name('marketing-campaigns.create'))->assertOk()->assertSee('نوع الحملة');
        if (getenv('AUCTION_REPORT_UI_PROOF')) {
            file_put_contents(base_path('output/cycle-review/campaign-form.html'), $form->getContent());
        }
        $this->post(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]])->assertRedirect(tenant_route_name('auctions.show', $auction));
        $this->get(tenant_route_name('auction-closings.index'))->assertOk();
        $closing = $this->get(tenant_route_name('auction-closings.edit', $auction))->assertOk();
        if (getenv('AUCTION_REPORT_UI_PROOF')) {
            file_put_contents(base_path('output/cycle-review/closing-form.html'), $closing->getContent());
        }
        $this->get(tenant_route_name('auction-reports.index'))->assertOk();
        $this->get(tenant_route_name('auctions.show', $auction))->assertOk()->assertSee('الحملات التسويقية');
        $campaign = $auction->marketingCampaigns()->first();
        $this->get(tenant_route_name('marketing-campaigns.edit', $campaign))->assertOk();
        $index = fn () => $this->getJson(tenant_route_name('auction-reports.index').'?draw=1&start=0&length=1000', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $response = $index();
        $rows = collect($response->json('data'))->filter(fn ($row) => str_starts_with($row['DT_RowId'] ?? '', 'report-'.$auction->id.'-'));
        self::assertCount(2, $rows, $response->getContent());
        self::assertCount(2, $rows->pluck('DT_RowId')->unique());
        app(AuctionReportService::class)->retryLatest($auction, (string) tenant('id'));
        self::assertCount(2, collect($index()->json('data'))->filter(fn ($row) => str_starts_with($row['DT_RowId'] ?? '', 'report-'.$auction->id.'-')));
        $this->withExceptionHandling();
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => ['x'], 'metrics' => 'invalid'])->assertUnprocessable();
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'whatsapp', 'metrics' => 'invalid'])->assertUnprocessable();
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => -1]])->assertUnprocessable()->assertJsonValidationErrors('metrics.views');
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => 99999999, 'channel' => 'x', 'metrics' => ['views' => 1]])->assertUnprocessable()->assertJsonValidationErrors('auction_id');
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => []])->assertUnprocessable()->assertJsonValidationErrors('metrics');
        $this->put(tenant_route_name('marketing-campaigns.update', $campaign), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 8]])->assertRedirect(tenant_route_name('auctions.show', $auction));
        $this->delete(tenant_route_name('marketing-campaigns.destroy', $campaign))->assertRedirect(tenant_route_name('auctions.show', $auction));
        self::assertSame([], $auction->latestReportGeneration()->first()->snapshot['marketing']['campaigns']);
        $property = $auction->properties()->first();
        $this->putJson(tenant_route_name('auction-closings.update', $auction), ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'sold', 'sale_price' => 500]]])->assertUnprocessable()->assertJsonValidationErrors('results.'.$property->id.'.buyer_name');
        $this->put(tenant_route_name('auction-closings.update', $auction), ['closing_date' => now()->toDateString(), 'results' => [$property->id => ['status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => 500]]])->assertRedirect(tenant_route_name('auctions.show', $auction));
        $this->postJson(tenant_route_name('auctions.reports.retry', $auction))->assertStatus(202);
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]])->assertUnprocessable();
        $ended = $this->getJson(tenant_route_name('auction-reports.index').'?draw=1&length=1000&content_status=ended', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        self::assertCount(2, collect($ended->json('data'))->filter(fn ($row) => str_starts_with($row['DT_RowId'] ?? '', 'report-'.$auction->id.'-')));
        $restricted = User::create(['name' => 'Restricted', 'email' => uniqid().'@report.test', 'password' => bcrypt('Fixture-only-123!'), 'status' => 'active']);
        $this->actingAs($restricted, 'tenant')->get(tenant_route_name('auction-reports.index'))->assertForbidden();
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]])->assertForbidden();
    }
}

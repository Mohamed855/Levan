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
        foreach (['marketing_campaigns.view', 'marketing_campaigns.create', 'marketing_campaigns.edit', 'marketing_campaigns.delete', 'auction_closings.view', 'auction_closings.create', 'auction_closings.edit'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'tenant']));
        }
        $this->actingAs($user, 'tenant');
        $this->withoutExceptionHandling();
        cache()->put('tenant:'.tenant('id').':subscription', (object) ['status' => null], 60);
        self::assertTrue($user->can('marketing_campaigns.create'), 'permission before index');
        $this->get(tenant_route_name('marketing-campaigns.index'))->assertOk();
        self::assertTrue($user->can('marketing_campaigns.create'), 'permission after index');
        $form = $this->get(tenant_route_name('marketing-campaigns.create'))->assertOk()
            ->assertSee('id="campaign-channels-select"', false)->assertSee('name="channels[]"', false);
        if (getenv('AUCTION_REPORT_UI_PROOF')) {
            file_put_contents(base_path('output/cycle-review/campaign-form.html'), $form->getContent());
        }
        $this->post(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]])->assertRedirect(tenant_route_name('auctions.show', $auction));
        $this->get(tenant_route_name('auction-closings.index'))->assertOk();
        $closing = $this->get(tenant_route_name('auction-closings.edit', $auction))->assertOk();
        if (getenv('AUCTION_REPORT_UI_PROOF')) {
            file_put_contents(base_path('output/cycle-review/closing-form.html'), $closing->getContent());
        }
        $this->get(tenant_route_name('auctions.show', $auction))->assertOk()->assertSee('الحملات التسويقية');
        $campaign = $auction->marketingCampaigns()->first();
        $this->get(tenant_route_name('marketing-campaigns.edit', $campaign))->assertOk();
        app(AuctionReportService::class)->retryLatest($auction, (string) tenant('id'));
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
        $restricted = User::create(['name' => 'Restricted', 'email' => uniqid().'@report.test', 'password' => bcrypt('Fixture-only-123!'), 'status' => 'active']);
        $this->actingAs($restricted, 'tenant')->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]])->assertForbidden();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionCyclePresentationTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_cycle_forms_are_single_page_and_indexes_use_platform_statistics_and_actions(): void
    {
        $this->withoutMiddleware(CheckSubscription::class);
        if (! getenv('AUCTION_REPORT_UI_PROOF')) {
            $this->withoutVite();
        }
        cache()->put('tenant:'.tenant('id').':subscription', (object) ['status' => null], 60);
        $user = User::where('email', 'demo@lot.com')->first();
        foreach (config('auction-report-permissions') as $permissions) {
            foreach ($permissions as $name) {
                $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'tenant']));
            }
        }
        $this->actingAs($user, 'tenant');
        $auction = $this->cycleAuction();
        foreach (['marketing-campaigns.create' => [], 'auction-closings.edit' => [$auction]] as $route => $parameters) {
            $response = $this->get(tenant_route_name($route, $parameters))->assertOk()->assertDontSee('data-form-stepper', false)->assertDontSee('data-cycle-review', false)->assertSee('data-form-validate', false);
            if (getenv('AUCTION_REPORT_UI_PROOF')) {
                file_put_contents(base_path('output/cycle-review/'.($route === 'marketing-campaigns.create' ? 'campaign' : 'closing').'-form.html'), $response->getContent());
            }
        }
        Queue::fake();
        app(SaveMarketingCampaignAction::class)->execute(null, ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['views' => 0]], null);
        foreach (['marketing-campaigns' => 'إجمالي الحملات', 'auction-closings' => 'مزادات لم تُقفل', 'auction-reports' => 'إجمالي التقارير'] as $resource => $label) {
            $response = $this->get(tenant_route_name($resource.'.index'))->assertOk()->assertSee($label);
            if ($resource === 'marketing-campaigns') {
                $response->assertSee('btn-add', false);
            }
            $table = $this->getJson(tenant_route_name($resource.'.index').'?draw=1&length=100', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
            self::assertStringContainsString('btn-icon', $table->json('data.0.actions'));
            if (getenv('AUCTION_REPORT_UI_PROOF')) {
                file_put_contents(base_path('output/cycle-review/'.$resource.'-index.html'), $response->getContent());
                file_put_contents(base_path('output/cycle-review/'.$resource.'-index.json'), $table->getContent());
            }
        }
    }
}

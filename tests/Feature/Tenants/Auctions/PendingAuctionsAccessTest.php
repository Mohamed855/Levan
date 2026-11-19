<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Events\Dashboard\NotificationsChanged;
use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense;
use App\Models\Tenants\UserMangment\User\User;
use App\Observers\DashboardActivityObserver;
use App\Services\Shared\Sidebar\SidebarCounterService;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class PendingAuctionsAccessTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckSubscription::class, EnsureUserHasActiveLicense::class]);
        cache()->put('tenant:'.tenant('id').':subscription', false, now()->addMinute());
        $user = User::query()->where('email', 'demo@lot.com')->firstOrFail();
        $user->syncRoles([]);
        $user->syncPermissions([]);
        $this->actingAs($user, 'tenant');
    }

    public function test_pending_list_requires_its_own_permission_even_for_auction_viewers(): void
    {
        $this->allow('auctions.view');
        $this->get(tenant_route_name('pending-auctions.index'))->assertForbidden();
        $this->getJson(tenant_route_name('pending-auctions.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertForbidden();
    }

    public function test_pending_list_shows_only_unapproved_auctions_and_eye_actions(): void
    {
        $this->allow('pending_auctions.view');
        $pending = $this->cycleAuction();
        $approved = $this->cycleAuction();
        $approved->establishmentRequest()->create(['submitted_at' => now(), 'data' => []])->forceFill(['approved_at' => now(), 'approval_number' => '123'])->save();

        $this->get(tenant_route_name('pending-auctions.index'))->assertOk()->assertSee('بانتظار الاعتماد');
        $response = $this->getJson(tenant_route_name('pending-auctions.index').'?draw=1&length=100', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        self::assertContains($pending->id, $ids);
        self::assertNotContains($approved->id, $ids);
        $row = collect($response->json('data'))->firstWhere('id', $pending->id);
        self::assertStringContainsString('ti-eye', $row['actions']);
        self::assertStringNotContainsString('ti-edit', $row['actions']);
        self::assertStringNotContainsString('ti-trash', $row['actions']);
        $this->get(tenant_route_name('auctions.show', $pending))->assertOk();
        $this->get(tenant_route_name('auctions.show', $approved))->assertForbidden();
    }

    public function test_view_permission_does_not_authorize_approval(): void
    {
        $this->allow('pending_auctions.view');
        $auction = $this->cycleAuction();
        $auction->establishmentRequest()->create(['submitted_at' => now(), 'data' => ['properties' => [['property_id' => $auction->properties()->first()->id]]]]);
        $url = tenant_route_name('auction-establishment-requests.approve', $auction);
        $this->post($url, ['approval_number' => '123'])->assertForbidden();
        self::assertNull($auction->establishmentRequest()->first()->approved_at);
        $this->allow('auction_establishment_requests.approve');
        $this->post($url, ['approval_number' => '123'])->assertSessionHasNoErrors()->assertRedirect(tenant_route_name('pending-auctions.index'));
        self::assertTrue($auction->establishmentRequest()->first()->isApproved());
    }

    public function test_permission_migration_is_repeatable_and_groups_view_and_approval(): void
    {
        $migration = require database_path('migrations/tenant/2026_11_19_160000_add_pending_auctions_permissions.php');
        $migration->up();
        $migration->up();
        foreach (['pending_auctions.view', 'auction_establishment_requests.approve'] as $name) {
            self::assertSame('pending_auctions_management', Permission::where('guard_name', 'tenant')->where('name', $name)->sole()->section);
        }
    }

    public function test_approval_button_modal_and_endpoint_require_both_permissions(): void
    {
        $auction = $this->cycleAuction();
        $auction->establishmentRequest()->create(['submitted_at' => now(), 'data' => []]);
        $details = tenant_route_name('auctions.show', $auction);
        $approve = tenant_route_name('auction-establishment-requests.approve', $auction);
        $this->allow('auctions.view');
        $this->allow('auction_establishment_requests.approve');
        $this->get($details)->assertOk()->assertDontSee('id="approveEstablishmentModal"', false)->assertDontSee('data-bs-target="#approveEstablishmentModal"', false);
        $this->post($approve, ['approval_number' => '123'])->assertForbidden();

        $this->allow('pending_auctions.view');
        $this->get($details)->assertOk()->assertSee('id="approveEstablishmentModal"', false)->assertSee('data-bs-target="#approveEstablishmentModal"', false);
        auth('tenant')->user()->revokePermissionTo('auction_establishment_requests.approve');
        $this->get($details)->assertOk()->assertDontSee('id="approveEstablishmentModal"', false)->assertDontSee('data-bs-target="#approveEstablishmentModal"', false);
    }

    public function test_counter_tracks_pending_auctions_and_hides_the_value_without_permission(): void
    {
        config(['dashboard-notifications.enabled' => true, 'dashboard-notifications.broadcast' => true]);
        Event::fake([NotificationsChanged::class]);
        $this->allow('pending_auctions.view');
        $service = app(SidebarCounterService::class);
        $user = auth('tenant')->user();
        $before = $service->getTenantCounts($user)['pending_auctions'];
        $auction = $this->cycleAuction();
        // Fixtures run inside a rollback transaction; invoke the after-commit handler explicitly.
        app(DashboardActivityObserver::class)->created($auction);
        self::assertSame($before + 1, $service->getTenantCounts($user)['pending_auctions']);
        Event::assertDispatched(NotificationsChanged::class, fn (NotificationsChanged $event): bool => $event->channel === 'dashboard.tenant.'.tenant('id').'.user.'.$user->id);
        $this->getJson(tenant_route_name('dashboard-notifications.feed'))->assertOk()->assertJsonPath('sidebar_counts.pending_auctions', $before + 1);
        $this->get(tenant_route_name('pending-auctions.index'))->assertOk()->assertSee('data-sidebar-counter="pending_auctions"', false);

        $auction->delete();
        app(DashboardActivityObserver::class)->deleted($auction);
        self::assertSame($before, $service->getTenantCounts($user)['pending_auctions']);
        $auction->restore();
        app(DashboardActivityObserver::class)->restored($auction);
        self::assertSame($before + 1, $service->getTenantCounts($user)['pending_auctions']);

        $request = $auction->establishmentRequest()->create(['submitted_at' => now(), 'data' => []]);
        $request->forceFill(['approved_at' => now(), 'approval_number' => '123'])->save();
        app(DashboardActivityObserver::class)->updated($request);
        self::assertSame($before, $service->getTenantCounts($user)['pending_auctions']);
        $user->revokePermissionTo('pending_auctions.view');
        self::assertSame(0, $service->getTenantCounts($user)['pending_auctions']);
        $this->getJson(tenant_route_name('dashboard-notifications.feed'))->assertOk()->assertJsonPath('sidebar_counts.pending_auctions', 0);
    }

    private function allow(string $permission): void
    {
        auth('tenant')->user()->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'tenant']));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTables\Admin\Subscriptions\SubscriptionDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminSubscriptionsFixesTest extends TestCase
{
    private AdminUser $admin;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::where('email', 'superadmin@gmail.com')->first()
            ?? AdminUser::firstOrFail();

        $this->city = City::firstOrFail();
        $this->actingAs($this->admin, 'admin');
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'id' => 'subfix_'.uniqid(),
            'company_name' => 'شركة اختبار الاشتراكات '.rand(100, 999),
            'commercial_number' => (string) rand(1000000000, 9999999999),
            'tax_number' => (string) rand(100000000000000, 999999999999999),
            'city_id' => $this->city->id,
            'address' => 'الرياض',
            'db_database' => 'subfix_db_'.uniqid(),
            'db_username' => 'root',
            'db_password' => 'secret_test_pw',
        ], $overrides));
    }

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'باقة اختبار '.uniqid(),
            'code' => 'SUBFIX-'.uniqid(),
            'price' => 100.00,
            'discount_percentage' => 0,
            'subscription_duration_type' => 'limited',
            'subscription_billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'included_users' => 5,
            'properties_limit' => 20,
            'storage_quota_gb' => 10,
            'active_auctions_limit' => 5,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_subscription_relations_resolve_soft_deleted_tenant_and_plan(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'auto_renew' => false,
        ]);

        try {
            $tenant->delete();
            Plan::whereKey($plan->id)->update(['deleted_at' => now()]);

            $fresh = $subscription->fresh(['tenant', 'plan']);
            $this->assertNotNull($fresh->tenant);
            $this->assertSame($tenant->company_name, $fresh->tenant->company_name);
            $this->assertNotNull($fresh->plan);
            $this->assertSame($plan->name, $fresh->plan->name);
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_toggle_status_rejects_expired_and_cancelled_subscriptions_with_422(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::EXPIRED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
            'auto_renew' => false,
        ]);

        try {
            $response = $this->patchJson("http://localhost/superadmin/subscriptions/{$subscription->id}/toggle-status");

            $response->assertStatus(422)->assertJson(['success' => false]);
            $this->assertSame(SubscriptionStatus::EXPIRED, $subscription->fresh()->status);

            $subscription->update(['status' => SubscriptionStatus::ACTIVE]);
            $okResponse = $this->patchJson("http://localhost/superadmin/subscriptions/{$subscription->id}/toggle-status");
            $okResponse->assertOk()->assertJson([
                'success' => true,
                'status' => 'suspended',
                'is_active' => false,
            ]);
            $this->assertSame(SubscriptionStatus::SUSPENDED, $subscription->fresh()->status);
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_expiring_soon_count_includes_active_subscriptions_without_periods(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $service = app(AdminSubscriptionService::class);
        $beforeCount = $service->getExpiringSoonCount();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        try {
            $this->assertSame($beforeCount + 1, $service->getExpiringSoonCount());
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_mass_delete_subscription_clears_tenant_and_sidebar_caches_and_sidebar_counts_cancelled_as_expired(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::CANCELLED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'auto_renew' => false,
        ]);

        try {
            Cache::put("tenant:{$tenant->id}:subscription", 'cached', 60);
            Cache::put('admin:expiring_subscriptions_count', 99, 60);
            Cache::put('admin:expired_subscriptions_count', 99, 60);

            $res = $this->postJson('http://localhost/superadmin/mass-delete', [
                'model' => 'Subscription',
                'ids' => (string) $subscription->id,
            ]);
            $res->assertOk()->assertJson(['success' => true]);

            $this->assertNull(Cache::get("tenant:{$tenant->id}:subscription"));
            $this->assertNull(Cache::get('admin:expiring_subscriptions_count'));
            $this->assertNull(Cache::get('admin:expired_subscriptions_count'));

            // Verify sidebar menu view counts CANCELLED in expired badge when rendered fresh
            $subCancelled = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'price' => 100.00,
                'status' => SubscriptionStatus::CANCELLED,
                'total_users' => 5,
                'used_users' => 0,
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->subDays(1),
                'auto_renew' => false,
            ]);

            $menuHtml = view('admin.layouts.sections.menu.verticalMenu')->render();
            $this->assertMatchesRegularExpression(
                '/data-sidebar-counter="subscriptions_expired">\s*[1-9]\d*\s*</',
                $menuHtml
            );

            $subCancelled->forceDelete();
        } finally {
            Bill::where('tenant_id', $tenant->id)->forceDelete();
            Subscription::where('tenant_id', $tenant->id)->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_subscription_datatable_preserves_id_and_handles_created_at_when_sorted_by_relations(): void
    {
        $tenant = $this->makeTenant(['company_name' => 'ZZZ Unique Sub Company']);
        $plan = $this->makePlan(['name' => 'ZZZ Unique Sub Plan']);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::EXPIRED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
            'auto_renew' => false,
        ]);

        try {
            $dt = app(SubscriptionDataTable::class);
            $colsMethod = new \ReflectionMethod($dt, 'getColumns');
            $colsMethod->setAccessible(true);
            $reqCols = array_map(fn (array $c): array => [
                'data' => $c['data'],
                'name' => $c['name'],
                'searchable' => ($c['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => ($c['orderable'] ?? true) ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ], $colsMethod->invoke($dt));

            foreach ([2, 3, 4] as $colIndex) {
                $response = $this->withHeaders([
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'application/json',
                ])->get('http://localhost/superadmin/subscriptions?'.http_build_query([
                    'draw' => 1,
                    'start' => 0,
                    'length' => 10,
                    'columns' => $reqCols,
                    'order' => [['column' => $colIndex, 'dir' => 'desc']],
                    'search' => ['value' => $subscription->subscription_number],
                    'created_from' => now()->subDay()->toDateString(),
                    'created_to' => now()->addDay()->toDateString(),
                ]));

                $response->assertOk();
                $json = $response->json();
                $this->assertArrayNotHasKey('error', $json);
                $this->assertCount(1, $json['data']);

                $row = $json['data'][0];
                $this->assertSame($subscription->id, $row['id']);
                $this->assertArrayNotHasKey('db_password', $row);
                $this->assertArrayNotHasKey('api_token', $row);
                $this->assertStringContainsString('منتهي', $row['status']);
                $this->assertStringNotContainsString('js-table-status-toggle', $row['status']);
            }
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_subscription_datatable_expiring_filter_only_includes_active_subscriptions(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        $plan = $this->makePlan();

        $activeExpiring = Subscription::create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        $cancelledSub = Subscription::create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::CANCELLED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        try {
            $dt = app(SubscriptionDataTable::class);
            $colsMethod = new \ReflectionMethod($dt, 'getColumns');
            $colsMethod->setAccessible(true);
            $reqCols = array_map(fn (array $c): array => [
                'data' => $c['data'],
                'name' => $c['name'],
                'searchable' => ($c['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => ($c['orderable'] ?? true) ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ], $colsMethod->invoke($dt));

            $response = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])->get('http://localhost/superadmin/subscriptions?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 100,
                'columns' => $reqCols,
                'expiring_in' => '30',
            ]));

            $response->assertOk();
            $ids = collect($response->json('data'))->pluck('id')->all();
            $this->assertContains($activeExpiring->id, $ids);
            $this->assertNotContains($cancelledSub->id, $ids);
        } finally {
            $activeExpiring->forceDelete();
            $cancelledSub->forceDelete();
            Tenant::withTrashed()->whereKey($tenantA->id)->forceDelete();
            Tenant::withTrashed()->whereKey($tenantB->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }
}

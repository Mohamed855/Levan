<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Plans\PlanService;
use App\Services\Shared\Subscription\SubscriptionManager;
use Carbon\Carbon;
use Database\Seeders\admin\PublicPlanSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

final class UnifiedPlanPricingFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::first() ?? AdminUser::factory()->create();
    }

    public function test_plan_model_pricing_helpers(): void
    {
        // 1. Plan with monthly price only
        $monthlyOnly = new Plan([
            'price' => 200.00,
            'yearly_price' => null,
            'discount_percentage' => 0,
        ]);

        $this->assertFalse($monthlyOnly->hasYearlyPrice());
        $this->assertTrue($monthlyOnly->supportsBillingCycle(SubscriptionBillingCycle::MONTHLY));
        $this->assertFalse($monthlyOnly->supportsBillingCycle(SubscriptionBillingCycle::YEARLY));
        $this->assertSame(200.0, $monthlyOnly->priceForCycle('monthly'));
        $this->assertNull($monthlyOnly->priceForCycle('yearly'));

        // 2. Plan with monthly and yearly price + discount
        $both = new Plan([
            'price' => 300.00,
            'yearly_price' => 3000.00,
            'discount_percentage' => 10,
        ]);

        $this->assertTrue($both->hasYearlyPrice());
        $this->assertTrue($both->supportsBillingCycle(SubscriptionBillingCycle::MONTHLY));
        $this->assertTrue($both->supportsBillingCycle(SubscriptionBillingCycle::YEARLY));
        $this->assertSame(270.0, $both->priceForCycle('monthly'));
        $this->assertSame(2700.0, $both->priceForCycle('yearly'));
    }

    public function test_landing_page_displays_monthly_only_plan_in_monthly_tab_but_not_in_yearly_tab(): void
    {
        $code = 'TEST_UNIFIED_'.Str::upper(Str::random(6));

        $plan = Plan::create([
            'name' => 'باقة شهرية تجريبية حصرية',
            'code' => $code,
            'plan_type' => PlanType::BASIC,
            'color' => PlanColor::PRIMARY,
            'price' => 199.00,
            'yearly_price' => null, // Monthly only
            'discount_percentage' => 0,
            'is_active' => true,
            'limits_mode' => PlanLimitsMode::LIMITED,
            'included_users' => 3,
            'extra_user_license_price' => 30.00,
            'properties_limit' => 10,
            'storage_quota_gb' => 5,
            'active_auctions_limit' => 2,
            'features' => ['ميزة أ'],
            'created_by' => $this->admin->id,
        ]);

        try {
            $response = $this->get(route('site.index'))->assertOk();

            // The plan should be rendered in the monthly grid
            $response->assertSee('باقة شهرية تجريبية حصرية');

            // Verify package card accepts monthly and yearly pricing
            $content = $response->getContent();

            // Under the yearly tab grid, this plan should NOT appear as a yearly option
            // Let's verify by checking checkout link with cycle=yearly for this plan
            $yearlyCheckoutUrl = route('site.plans.checkout', ['plan' => $plan->id, 'cycle' => 'yearly']);
            $this->assertStringNotContainsString($yearlyCheckoutUrl, $content);

            // Under the monthly grid, the checkout link with cycle=monthly should appear
            $monthlyCheckoutUrl = route('site.plans.checkout', ['plan' => $plan->id, 'cycle' => 'monthly']);
            $this->assertStringContainsString($monthlyCheckoutUrl, $content);
        } finally {
            $plan->forceDelete();
        }
    }

    public function test_landing_page_displays_dual_price_plan_in_both_tabs(): void
    {
        $code = 'TEST_DUAL_'.Str::upper(Str::random(6));

        $plan = Plan::create([
            'name' => 'باقة مزدوجة تجريبية متكاملة',
            'code' => $code,
            'plan_type' => PlanType::PROFESSIONAL,
            'color' => PlanColor::GREEN,
            'price' => 200.00,
            'yearly_price' => 2000.00,
            'discount_percentage' => 0,
            'is_active' => true,
            'limits_mode' => PlanLimitsMode::LIMITED,
            'included_users' => 5,
            'extra_user_license_price' => 50.00,
            'properties_limit' => 50,
            'storage_quota_gb' => 20,
            'active_auctions_limit' => 10,
            'features' => ['ميزة شاملة'],
            'created_by' => $this->admin->id,
        ]);

        try {
            $response = $this->get(route('site.index'))->assertOk();

            $content = $response->getContent();

            // Both monthly and yearly checkout links must be present
            $monthlyCheckoutUrl = route('site.plans.checkout', ['plan' => $plan->id, 'cycle' => 'monthly']);
            $yearlyCheckoutUrl = route('site.plans.checkout', ['plan' => $plan->id, 'cycle' => 'yearly']);

            $this->assertStringContainsString($monthlyCheckoutUrl, $content);
            $this->assertStringContainsString($yearlyCheckoutUrl, $content);
        } finally {
            $plan->forceDelete();
        }
    }

    public function test_checkout_page_renders_correct_cycle_and_pricing(): void
    {
        $code = 'TEST_CHK_'.Str::upper(Str::random(6));

        $plan = Plan::create([
            'name' => 'باقة اختبار الدفع',
            'code' => $code,
            'plan_type' => PlanType::ENTERPRISE,
            'color' => PlanColor::PRIMARY,
            'price' => 350.00,
            'yearly_price' => 3500.00,
            'discount_percentage' => 10,
            'is_active' => true,
            'limits_mode' => PlanLimitsMode::UNLIMITED,
            'features' => ['كل الميزات'],
            'created_by' => $this->admin->id,
        ]);

        try {
            // 1. Monthly checkout (350 - 10% = 315)
            $responseMonthly = $this->get(route('site.plans.checkout', ['plan' => $plan->id, 'cycle' => 'monthly']))
                ->assertOk();
            $responseMonthly->assertSee('شهرياً');
            $responseMonthly->assertSee('315.00');

            // 2. Yearly checkout (3500 - 10% = 3150)
            $responseYearly = $this->get(route('site.plans.checkout', ['plan' => $plan->id, 'cycle' => 'yearly']))
                ->assertOk();
            $responseYearly->assertSee('سنوياً');
            $responseYearly->assertSee('3,150.00');
        } finally {
            $plan->forceDelete();
        }
    }

    public function test_subscription_creation_and_period_end_for_each_cycle(): void
    {
        $plan = Plan::query()->where('code', '!=', 'trial_7_days')->whereNotNull('yearly_price')->firstOrFail();

        $subscriptionManager = app(SubscriptionManager::class);

        // Test period end calculation
        $start = Carbon::parse('2026-01-01');

        $refMethod = new \ReflectionMethod($subscriptionManager, 'calculatePeriodEndForPlan');
        $refMethod->setAccessible(true);

        $endMonthly = $refMethod->invoke($subscriptionManager, $start, $plan, 'monthly');
        $this->assertSame('2026-02-01', $endMonthly->format('Y-m-d'));

        $endYearly = $refMethod->invoke($subscriptionManager, $start, $plan, 'yearly');
        $this->assertSame('2027-01-01', $endYearly->format('Y-m-d'));
    }

    public function test_subscription_renewal_allows_cycle_switching_and_period_calculation(): void
    {
        $tenant = Tenant::find('test');
        if (! $tenant) {
            $this->markTestSkipped('Test tenant [test] not found in current environment.');
        }

        $subscription = Subscription::where('tenant_id', 'test')->latest('id')->firstOrFail();

        $originalCycle = $subscription->billing_cycle;
        $originalEndsAt = $subscription->ends_at;

        try {
            // Set initial subscription as monthly
            $subscription->update([
                'billing_cycle' => SubscriptionBillingCycle::MONTHLY,
                'ends_at' => now()->addDays(15),
            ]);

            $subscriptionManager = app(SubscriptionManager::class);

            // Renew with 'yearly' cycle
            [$renewedSub] = $subscriptionManager->renewSubscription($tenant, 2000.00, 'yearly');

            $this->assertSame(SubscriptionBillingCycle::YEARLY, $renewedSub->billing_cycle);

            $period = $renewedSub->periods()->latest('id')->firstOrFail();
            $this->assertSame('yearly', $period->billing_cycle_snapshot);

            // Period duration should be 1 year
            $periodStart = Carbon::parse($period->period_start);
            $periodEnd = Carbon::parse($period->period_end);
            $this->assertEquals(1, $periodStart->diffInYears($periodEnd));

            $period->delete();
        } finally {
            $subscription->update([
                'billing_cycle' => $originalCycle,
                'ends_at' => $originalEndsAt,
            ]);
        }
    }

    public function test_plan_creation_request_requires_yearly_price(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.plans.store'), [
            'name' => 'باقة بدون سنوي',
            'code' => 'TEST_NO_YEARLY',
            'plan_type' => PlanType::BASIC->value,
            'color' => PlanColor::PRIMARY->value,
            'price' => 100,
            'yearly_price' => '', // missing yearly price
            'discount_percentage' => 0,
            'is_active' => true,
            'limits_mode' => PlanLimitsMode::LIMITED->value,
            'included_users' => 5,
            'extra_user_license_price' => 50,
            'properties_limit' => 20,
            'storage_quota_gb' => 10,
            'active_auctions_limit' => 5,
            'features' => ['ميزة'],
        ]);

        $response->assertSessionHasErrors(['yearly_price']);
    }

    public function test_trial_plan_is_excluded_from_admin_plans_datatable_and_statistics(): void
    {
        $trial = Plan::query()->where('code', 'trial_7_days')->firstOrFail();

        $stats = app(PlanService::class)->getPlanStatistics();
        $this->assertSame(Plan::notTrial()->count(), $stats['total']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.plans.index'), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $row) {
            $this->assertStringNotContainsString('trial_7_days', (string) ($row['code'] ?? ''));
            $this->assertStringNotContainsString($trial->name, (string) ($row['name'] ?? ''));
        }
    }

    public function test_trial_plan_cannot_be_edited_updated_toggled_or_deleted(): void
    {
        $trial = Plan::query()->where('code', 'trial_7_days')->firstOrFail();

        // 1. Edit route blocked
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.plans.edit', $trial->id))
            ->assertRedirect(route('admin.plans.index'))
            ->assertSessionHas('error');

        // 2. Update route blocked
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.plans.update', $trial->id), [
                'name' => 'تعديل التجريبية',
                'plan_type' => PlanType::BASIC->value,
                'color' => PlanColor::PRIMARY->value,
                'price' => 100,
                'yearly_price' => 1000,
                'discount_percentage' => 0,
                'is_active' => true,
                'limits_mode' => PlanLimitsMode::UNLIMITED->value,
                'features' => ['ميزة'],
            ])
            ->assertRedirect(route('admin.plans.index'))
            ->assertSessionHas('error');

        // 3. Toggle active blocked
        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.plans.toggle-active', $trial->id))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // 4. Destroy route blocked
        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.plans.destroy', $trial->id))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // 5. Service domain exceptions
        $service = app(PlanService::class);
        $this->expectException(\DomainException::class);
        $service->delete($trial);
    }

    public function test_public_plan_seeder_creates_three_arabic_plans(): void
    {
        $this->seed(PublicPlanSeeder::class);

        $codes = ['PLAN_BASIC', 'PLAN_PRO', 'PLAN_ENTERPRISE'];
        $plans = Plan::query()->whereIn('code', $codes)->get();

        $this->assertCount(3, $plans);
        foreach ($plans as $p) {
            $this->assertNotEmpty($p->name);
            $this->assertGreaterThan(0, (float) $p->price);
            $this->assertGreaterThan(0, (float) $p->yearly_price);
            $this->assertTrue($p->hasYearlyPrice());
            $this->assertTrue($p->is_active);
            $this->assertNotEmpty($p->features);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Models\Admin\Plans\Plan;
use App\Services\Admin\Plans\PlanService;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class PublicPlanVisibilityFeatureTest extends TestCase
{
    public function test_trial_plan_is_not_rendered_or_reachable_from_public_checkout(): void
    {
        $trial = Plan::query()->where('code', 'trial_7_days')->first();
        $this->assertNotNull($trial);

        $this->get(route('site.index'))
            ->assertOk()
            ->assertDontSee($trial->name);

        $this->get(route('site.plans.checkout', $trial))
            ->assertNotFound();
    }

    public function test_superadmin_company_creation_keeps_trial_plan_available(): void
    {
        $trial = Plan::query()->where('code', 'trial_7_days')->first();
        $this->assertNotNull($trial);

        $this->assertTrue(Plan::active()->whereKey($trial)->exists());
    }

    public function test_trial_plan_is_configured_for_7_days_not_a_month(): void
    {
        $trial = Plan::query()->where('code', 'trial_7_days')->firstOrFail();
        $this->assertTrue($trial->isTrial());
        $this->assertNull($trial->subscription_billing_cycle);
        $this->assertSame('limited', $trial->subscription_duration_type);

        $subscriptionService = app(AdminSubscriptionService::class);
        $startDate = now();
        $refMethod = new \ReflectionMethod($subscriptionService, 'calculatePeriodEndForPlan');
        $refMethod->setAccessible(true);
        $endDate = $refMethod->invoke($subscriptionService, $startDate, $trial);

        $this->assertSame(7, (int) $startDate->diffInDays($endDate));
    }

    public function test_package_cards_render_with_custom_color_from_database(): void
    {
        $plan = Plan::query()->where('code', '!=', 'trial_7_days')->first();
        if ($plan) {
            $originalColor = $plan->color;
            try {
                $plan->update(['color' => PlanColor::BLUE]);

                $response = $this->get(route('site.index'))->assertOk();
                $response->assertSee('data-plan-color="blue"', false);
                $response->assertSee('--package-card-accent: #0284c7;', false);
            } finally {
                $plan->update(['color' => $originalColor]);
            }
        }
    }

    public function test_landing_page_renders_plans_with_null_code(): void
    {
        $plan = Plan::publiclyAvailable()->first();
        $this->assertNotNull($plan);

        $originalCode = $plan->code;
        try {
            $plan->update(['code' => null]);
            $response = $this->get(route('site.index'))->assertOk();
            $response->assertSee($plan->name);
        } finally {
            $plan->update(['code' => $originalCode]);
        }
    }

    public function test_landing_page_renders_most_requested_badge(): void
    {
        $plan = Plan::publiclyAvailable()->first();
        $this->assertNotNull($plan);

        Cache::put(PlanService::MOST_REQUESTED_CACHE_KEY, $plan->id, 3600);

        try {
            $response = $this->get(route('site.index'))->assertOk();
            $response->assertSee('package-card--popular');
            $response->assertSee('الأكثر طلباً');
        } finally {
            Cache::forget(PlanService::MOST_REQUESTED_CACHE_KEY);
        }
    }

    public function test_landing_page_renders_extra_user_license_note_for_limited_user_plans(): void
    {
        $plan = Plan::query()->where('code', '!=', 'trial_7_days')->whereNotNull('included_users')->first();
        if ($plan) {
            $originalExtra = $plan->extra_user_license_price;
            try {
                $plan->update(['extra_user_license_price' => 45.00]);
                $response = $this->get(route('site.index'))->assertOk();
                $response->assertSee('/ مستخدم إضافي');
                $response->assertSee('45.00');
            } finally {
                $plan->update(['extra_user_license_price' => $originalExtra]);
            }
        }
    }

    public function test_landing_page_renders_unlimited_users_without_extra_license_note(): void
    {
        $plan = Plan::query()->where('code', '!=', 'trial_7_days')->first();
        if ($plan) {
            $originalIncluded = $plan->included_users;
            $originalExtra = $plan->extra_user_license_price;
            try {
                $plan->update([
                    'included_users' => null,
                    'extra_user_license_price' => null,
                ]);
                $response = $this->get(route('site.index'))->assertOk();
                $response->assertSee('غير محدود');
            } finally {
                $plan->update([
                    'included_users' => $originalIncluded,
                    'extra_user_license_price' => $originalExtra,
                ]);
            }
        }
    }

    public function test_landing_page_renders_pricing_switcher_without_emojis(): void
    {
        $response = $this->get(route('site.index'))->assertOk();

        // Switcher markup and accessibility
        $response->assertSee('data-pricing-switcher', false);
        $response->assertSee('data-billing-target="monthly"', false);
        $response->assertSee('data-billing-target="yearly"', false);
        $response->assertSee('شهرياً');
        $response->assertSee('سنوياً');

        // Verify zero emojis in switcher markup
        $content = $response->getContent();
        $this->assertStringNotContainsString('📅', $content);
        $this->assertStringNotContainsString('🌟', $content);
        $this->assertStringNotContainsString('✨', $content);

        // Verify pricing grid data attributes
        $response->assertSee('data-pricing-grid', false);
        $response->assertSee('data-billing-cycle=', false);
    }

    public function test_plan_with_future_availability_date_does_not_appear_on_landing_page_and_aborts_checkout(): void
    {
        $plan = Plan::query()->where('code', '!=', 'trial_7_days')->first();
        if ($plan) {
            $originalAvailableFrom = $plan->available_from;
            try {
                $plan->update(['available_from' => now()->addDays(15)]);

                // Must not be considered publicly available
                $this->assertFalse(Plan::publiclyAvailable()->whereKey($plan->id)->exists());

                // Landing page must not show it
                $this->get(route('site.index'))
                    ->assertOk()
                    ->assertDontSee($plan->name);

                // Checkout must 404
                $this->get(route('site.plans.checkout', $plan->id))
                    ->assertNotFound();
            } finally {
                $plan->update(['available_from' => $originalAvailableFrom]);
            }
        }
    }

    public function test_subscription_billing_cycle_enum_only_contains_monthly_and_yearly(): void
    {
        $cases = array_map(fn (SubscriptionBillingCycle $case) => $case->value, SubscriptionBillingCycle::cases());

        $this->assertEqualsCanonicalizing(['monthly', 'yearly'], $cases);
        $this->assertNotContains('quarterly', $cases);
    }
}

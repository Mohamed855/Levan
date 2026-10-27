<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Plans\Plan;
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

        $subscriptionService = app(\App\Services\Admin\Subscriptions\AdminSubscriptionService::class);
        $startDate = now();
        $refMethod = new \ReflectionMethod($subscriptionService, 'calculatePeriodEndForPlan');
        $refMethod->setAccessible(true);
        $endDate = $refMethod->invoke($subscriptionService, $startDate, $trial);

        $this->assertSame(7, (int) $startDate->diffInDays($endDate));
    }
}

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
}

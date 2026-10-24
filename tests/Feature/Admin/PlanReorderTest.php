<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Plans\ReorderPlansAction;
use App\Models\Admin\Plans\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlanReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_complete_plan_order_atomically(): void
    {
        $first = Plan::query()->create($this->data('FIRST', 0));
        $second = Plan::query()->create($this->data('SECOND', 1));

        app(ReorderPlansAction::class)->execute([$first->id => 1, $second->id => 0]);

        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(0, $second->fresh()->sort_order);
    }

    private function data(string $code, int $sortOrder): array
    {
        return ['name' => $code, 'code' => $code, 'plan_type' => 'basic', 'color' => 'primary', 'price' => 100, 'extra_user_license_price' => 10, 'limits_mode' => 'limited', 'included_users' => 1, 'properties_limit' => 1, 'storage_quota_gb' => 1, 'active_auctions_limit' => 1, 'subscription_billing_cycle' => 'monthly', 'features' => ['ميزة'], 'is_active' => true, 'sort_order' => $sortOrder];
    }
}

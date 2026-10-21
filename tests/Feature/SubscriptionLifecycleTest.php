<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Admin\Subscriptions\CreateSubscriptionAction;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\TestCase;

final class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_subscription_records_an_immutable_current_period_snapshot(): void
    {
        Event::fake([TenantCreated::class]);

        $plan = Plan::query()->create([
            'name' => 'احترافية', 'code' => 'pro', 'price' => 100, 'limits_mode' => 'limited',
            'included_users' => 3, 'properties_limit' => 20, 'storage_quota_gb' => 5,
            'active_auctions_limit' => 2, 'features' => ['exports'], 'subscription_billing_cycle' => 'monthly',
        ]);
        $tenant = Tenant::query()->create([
            'company_name' => 'شركة اختبار', 'commercial_number' => '1012345678',
            'primary_contact_name' => 'مسؤول الاختبار', 'primary_contact_email' => 'owner@example.test',
            'primary_contact_phone' => '0500000000',
        ]);

        $subscription = app(CreateSubscriptionAction::class)->execute($tenant, $plan, 85.00);

        $this->assertSame('active', $subscription->status);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'tenant_id' => $tenant->id, 'price' => 85]);
        $this->assertDatabaseHas('subscription_periods', [
            'subscription_id' => $subscription->id, 'period_number' => 1,
            'plan_code_snapshot' => 'pro', 'is_current' => true,
        ]);
        $this->assertCount(1, Subscription::query()->findOrFail($subscription->id)->periods);
    }
}

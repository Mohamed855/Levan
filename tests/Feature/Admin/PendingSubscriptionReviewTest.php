<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Subscriptions\ReviewPendingSubscriptionsAction;
use App\Actions\Admin\Subscriptions\UpdateSubscriptionStatusAction;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Subscriptions\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\TestCase;

final class PendingSubscriptionReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_a_pending_subscription_activates_its_latest_period_without_settling_bills(): void
    {
        [$subscription, $period] = $this->pendingSubscription();

        app(ReviewPendingSubscriptionsAction::class)->accept($subscription);

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('active', $period->fresh()->status);
        $this->assertTrue((bool) $period->fresh()->is_current);
    }

    public function test_rejecting_a_pending_subscription_records_the_reason_and_deactivates_its_period(): void
    {
        [$subscription, $period] = $this->pendingSubscription();

        app(ReviewPendingSubscriptionsAction::class)->reject($subscription, 'بيانات الطلب غير مكتملة');

        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSame('بيانات الطلب غير مكتملة', $subscription->fresh()->cancellation_reason);
        $this->assertSame('cancelled', $period->fresh()->status);
        $this->assertFalse((bool) $period->fresh()->is_current);
    }

    public function test_general_pending_activation_also_marks_the_latest_period_as_current(): void
    {
        [$subscription, $period] = $this->pendingSubscription();

        app(UpdateSubscriptionStatusAction::class)->execute($subscription, 'active');

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('active', $period->fresh()->status);
        $this->assertTrue((bool) $period->fresh()->is_current);
    }

    public function test_status_transition_is_revalidated_after_the_subscription_row_is_locked(): void
    {
        [$subscription] = $this->pendingSubscription();
        $subscription->update(['status' => 'cancelled']);

        try {
            app(UpdateSubscriptionStatusAction::class)->execute($subscription, 'active');
            $this->fail('A stale pending-to-active request must not reactivate a cancelled subscription.');
        } catch (ValidationException) {
            $this->assertSame('cancelled', $subscription->fresh()->status);
        }
    }

    /** @return array{Subscription, SubscriptionPeriod} */
    private function pendingSubscription(): array
    {
        Event::fake([TenantCreated::class]);
        $plan = Plan::query()->create(['name' => 'باقة', 'code' => 'pending-plan', 'price' => 100, 'limits_mode' => 'limited', 'included_users' => 1, 'properties_limit' => 1, 'storage_quota_gb' => 1, 'active_auctions_limit' => 1, 'subscription_billing_cycle' => 'monthly']);
        $tenant = Tenant::query()->create(['company_name' => 'شركة اختبار', 'commercial_number' => '1012345678', 'primary_contact_name' => 'مسؤول', 'primary_contact_email' => 'owner@example.test', 'primary_contact_phone' => '0500000000']);
        $subscription = Subscription::query()->create(['subscription_number' => 'SUB-PENDING-1', 'tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'status' => 'pending', 'price' => 100, 'total_users' => 1, 'used_users' => 1, 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        $period = SubscriptionPeriod::query()->create(['subscription_id' => $subscription->id, 'period_number' => 1, 'period_start' => now(), 'period_end' => now()->addMonth(), 'plan_id_snapshot' => $plan->id, 'plan_code_snapshot' => $plan->code, 'plan_name_snapshot' => $plan->name, 'plan_type_snapshot' => 'basic', 'billing_cycle_snapshot' => 'monthly', 'limits_mode_snapshot' => 'limited', 'price_snapshot' => 100, 'total_users_snapshot' => 1, 'used_users_snapshot' => 1, 'status' => 'pending', 'is_current' => false]);

        return [$subscription, $period];
    }
}

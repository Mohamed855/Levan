<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Jobs\Admin\ProcessSubscriptionPeriods;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Notifications\NotificationDelivery;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Notifications\NotificationDeadlineScanner;
use App\Services\shared\Subscription\SubscriptionManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\TenantAuctionReportTestCase;

class SubscriptionDatesAndPeriodsLifecycleTest extends TenantAuctionReportTestCase
{
    private Plan $plan;

    private User $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = AdminUser::first();

        $this->plan = Plan::create([
            'name' => 'باقة اختبار دورة الحياة',
            'code' => 'TEST-LIFECYCLE-'.uniqid(),
            'price' => 100.00,
            'subscription_billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'included_users' => 5,
            'properties_limit' => 20,
            'storage_quota_gb' => 10,
            'active_auctions_limit' => 5,
            'is_active' => true,
            'created_by' => $admin?->id ?? 1,
        ]);

        Subscription::where('tenant_id', $this->tenant->id)->delete();
        config(['dashboard-notifications.enabled' => true, 'dashboard-notifications.broadcast' => false]);

        $this->tenantUser = User::firstOrCreate(
            ['email' => 'tenant-lifecycle-tester@example.test'],
            [
                'name' => 'مستخدم اختبار الاشتراك',
                'password' => bcrypt('Password123!'),
                'status' => 'active',
            ]
        );

        $this->tenant->update(['primary_user_global_uid' => $this->tenantUser->global_uid]);

        $this->actingAs($this->tenantUser, 'tenant');
    }

    public function test_initial_subscription_and_period_dates_match(): void
    {
        $manager = app(SubscriptionManager::class);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE
        );

        $subscription->refresh();
        $currentPeriod = $subscription->currentPeriod;

        self::assertNotNull($currentPeriod);
        self::assertTrue($currentPeriod->is_current);
        self::assertSame(SubscriptionPeriodStatus::ACTIVE, $currentPeriod->status);

        // starts_at / ends_at in subscriptions table
        self::assertNotNull($subscription->starts_at);
        self::assertNotNull($subscription->ends_at);

        // period_start / period_end in subscription_periods table
        self::assertEquals(
            $subscription->starts_at->toDateTimeString(),
            $currentPeriod->period_start->toDateTimeString()
        );
        self::assertEquals(
            $subscription->ends_at->toDateTimeString(),
            $currentPeriod->period_end->toDateTimeString()
        );

        // Snapshot of plan specifications captured in period
        self::assertSame($this->plan->id, $currentPeriod->plan_id_snapshot);
        self::assertSame('100.00', (string) $currentPeriod->price_snapshot);
        self::assertSame(5, $currentPeriod->total_users_snapshot);
    }

    public function test_advance_renewal_extends_subscription_ends_at_and_creates_queued_period(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        $firstPeriod = $subscription->currentPeriod;
        $firstPeriodEnd = $firstPeriod->period_end->copy();

        // Customer renews 10 days before period 1 expires
        $this->travelTo($startDate->copy()->addDays(20));
        $manager->renewSubscription($this->tenant, 100.00);

        $subscription->refresh();
        $periods = $subscription->periods()->orderBy('period_number')->get();

        self::assertCount(2, $periods);

        // Period 1 remains current and active until its period_end
        $period1 = $periods[0];
        self::assertTrue($period1->is_current);
        self::assertSame(SubscriptionPeriodStatus::ACTIVE, $period1->status);
        self::assertEquals($firstPeriodEnd->toDateTimeString(), $period1->period_end->toDateTimeString());

        // Period 2 is queued for the future
        $period2 = $periods[1];
        self::assertFalse($period2->is_current);
        self::assertSame(SubscriptionPeriodStatus::QUEUED, $period2->status);
        self::assertEquals($period1->period_end->toDateTimeString(), $period2->period_start->toDateTimeString());

        // Subscriptions table ends_at is extended to cover the full renewal horizon
        self::assertEquals($period2->period_end->toDateTimeString(), $subscription->ends_at->toDateTimeString());
        self::assertTrue($subscription->ends_at->gt($period1->period_end));
    }

    public function test_multiple_advance_renewals_accumulate_queued_periods_and_extend_ends_at(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        // Advance renewal 1
        $this->travelTo($startDate->copy()->addDays(10));
        $manager->renewSubscription($this->tenant, 100.00);

        // Advance renewal 2
        $this->travelTo($startDate->copy()->addDays(15));
        $manager->renewSubscription($this->tenant, 100.00);

        $subscription->refresh();
        $periods = $subscription->periods()->orderBy('period_number')->get();

        self::assertCount(3, $periods);

        // Period 1 is ACTIVE
        self::assertTrue($periods[0]->is_current);
        self::assertSame(SubscriptionPeriodStatus::ACTIVE, $periods[0]->status);

        // Period 2 and 3 are QUEUED
        self::assertFalse($periods[1]->is_current);
        self::assertSame(SubscriptionPeriodStatus::QUEUED, $periods[1]->status);
        self::assertFalse($periods[2]->is_current);
        self::assertSame(SubscriptionPeriodStatus::QUEUED, $periods[2]->status);

        // Period 2 starts when Period 1 ends
        self::assertEquals($periods[0]->period_end->toDateTimeString(), $periods[1]->period_start->toDateTimeString());

        // Period 3 starts when Period 2 ends
        self::assertEquals($periods[1]->period_end->toDateTimeString(), $periods[2]->period_start->toDateTimeString());

        // Overall subscription ends_at covers through Period 3
        self::assertEquals($periods[2]->period_end->toDateTimeString(), $subscription->ends_at->toDateTimeString());
    }

    public function test_renewal_warning_alert_and_modal_are_silenced_when_upcoming_queued_periods_exist(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        // Customer renews early on day 20
        $this->travelTo($startDate->copy()->addDays(20));
        $manager->renewSubscription($this->tenant, 100.00);

        // Travel to 5 days before Period 1 ends (day 26 of 31)
        $this->travelTo($startDate->copy()->addDays(26));

        // Clear cache to ensure fresh render
        cache()->forget("tenant:{$this->tenant->id}:subscription");

        // Render the subscription alert Blade section
        $viewHtml = view('tenant.layouts.sections.subscription_alert')->render();

        // Must NOT render warning alert, danger alert, or expiring modal
        self::assertStringNotContainsString('تنبيه باقتراب موعد تجديد الاشتراك', $viewHtml);
        self::assertStringNotContainsString('تحذير عاجل: اشتراك الشركة شارف على الانتهاء', $viewHtml);
        self::assertStringNotContainsString('subscriptionExpiringModal', $viewHtml);
    }

    public function test_renewal_warning_alert_and_modal_display_when_no_queued_period_exists(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        // Travel to 5 days before Period 1 ends (day 26 of 31) WITHOUT renewing
        $this->travelTo($startDate->copy()->addDays(26));

        cache()->forget("tenant:{$this->tenant->id}:subscription");

        $viewHtml = view('tenant.layouts.sections.subscription_alert')->render();

        // Must display the danger warning because no future renewal period exists!
        self::assertStringContainsString('تحذير عاجل: اشتراك الشركة شارف على الانتهاء', $viewHtml);

        // When accessed on a dashboard route, the modal must also be rendered
        $request = \Illuminate\Http\Request::create('http://test.localhost/dashboard');
        $route = new \Illuminate\Routing\Route('GET', 'dashboard', fn() => 'ok');
        $route->name('tenant.dashboard');
        $route->bind($request);
        $request->setRouteResolver(fn() => $route);
        app()->instance('request', $request);

        $ref = new \ReflectionProperty(app('router'), 'current');
        $ref->setValue(app('router'), $route);

        $dashboardHtml = view('tenant.layouts.sections.subscription_alert')->render();
        self::assertStringContainsString('subscriptionExpiringModal', $dashboardHtml);
    }

    public function test_notification_deadline_scanner_silences_notifications_when_upcoming_queued_period_exists(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        // Advance renewal
        $this->travelTo($startDate->copy()->addDays(20));
        $manager->renewSubscription($this->tenant, 100.00);

        // Advance to 5 days before period 1 ends
        $this->travelTo($startDate->copy()->addDays(26));

        $deliveryCountBefore = NotificationDelivery::count();

        // Run scanner
        $scanner = app(NotificationDeadlineScanner::class);
        $scanner->subscription($subscription->fresh());

        // No expiration notices should be created
        self::assertSame($deliveryCountBefore, NotificationDelivery::count());
    }

    public function test_notification_deadline_scanner_fires_notifications_when_no_queued_period_exists(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        // Advance to 5 days before period 1 ends WITHOUT renewing
        $this->travelTo($startDate->copy()->addDays(26));

        $beforeCount = NotificationDelivery::where('payload->event', 'subscription.expiring7')->count();

        $scanner = app(NotificationDeadlineScanner::class);
        $scanner->subscription($subscription->fresh());

        $afterCount = NotificationDelivery::where('payload->event', 'subscription.expiring7')->count();

        // Expiration notification should fire!
        self::assertGreaterThan($beforeCount, $afterCount);
    }

    public function test_process_subscription_periods_transitions_sequentially_and_expires_after_last_period(): void
    {
        $manager = app(SubscriptionManager::class);

        $startDate = Carbon::parse('2026-01-01 00:00:00');
        $this->travelTo($startDate);

        $subscription = $manager->createSubscription(
            $this->tenant,
            $this->plan,
            100.00,
            SubscriptionStatus::ACTIVE,
            SubscriptionPeriodStatus::ACTIVE,
            null,
            ['start_date' => $startDate]
        );

        // Advance renewal 1
        $this->travelTo($startDate->copy()->addDays(15));
        $manager->renewSubscription($this->tenant, 100.00);

        // Advance renewal 2
        $this->travelTo($startDate->copy()->addDays(20));
        $manager->renewSubscription($this->tenant, 100.00);

        $periods = $subscription->periods()->orderBy('period_number')->get();
        $period1 = $periods[0];
        $period2 = $periods[1];
        $period3 = $periods[2];

        // 1. Travel to when Period 1 ends (Period 2 starts)
        $this->travelTo($period1->period_end->copy()->addSecond());
        (new ProcessSubscriptionPeriods())->handle();

        $period1->refresh();
        $period2->refresh();
        $period3->refresh();
        $subscription->refresh();

        self::assertSame(SubscriptionPeriodStatus::ENDED, $period1->status);
        self::assertFalse($period1->is_current);

        self::assertSame(SubscriptionPeriodStatus::ACTIVE, $period2->status);
        self::assertTrue($period2->is_current);

        self::assertSame(SubscriptionPeriodStatus::QUEUED, $period3->status);
        self::assertFalse($period3->is_current);

        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->status);

        // 2. Travel to when Period 2 ends (Period 3 starts)
        $this->travelTo($period2->period_end->copy()->addSecond());
        (new ProcessSubscriptionPeriods())->handle();

        $period2->refresh();
        $period3->refresh();
        $subscription->refresh();

        self::assertSame(SubscriptionPeriodStatus::ENDED, $period2->status);
        self::assertFalse($period2->is_current);

        self::assertSame(SubscriptionPeriodStatus::ACTIVE, $period3->status);
        self::assertTrue($period3->is_current);

        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->status);

        // 3. Travel to when Period 3 ends (no more queued periods)
        $this->travelTo($period3->period_end->copy()->addSecond());
        (new ProcessSubscriptionPeriods())->handle();

        $period3->refresh();
        $subscription->refresh();

        self::assertSame(SubscriptionPeriodStatus::ENDED, $period3->status);
        self::assertFalse($period3->is_current);

        // Subscription is now EXPIRED
        self::assertSame(SubscriptionStatus::EXPIRED, $subscription->status);
    }
}

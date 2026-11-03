<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Services\Shared\Subscription\SubscriptionManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExpiringSubscriptionDaysRemainingTest extends TestCase
{
    public function test_calculate_days_remaining_with_subscription_period(): void
    {
        $manager = app(SubscriptionManager::class);
        $period = new SubscriptionPeriod([
            'period_end' => Carbon::now()->addDays(5),
        ]);

        $days = $manager->calculateDaysRemaining($period);

        $this->assertSame(5, $days);
    }

    public function test_calculate_days_remaining_with_fallback_ends_at_when_period_is_null(): void
    {
        $manager = app(SubscriptionManager::class);

        $days = $manager->calculateDaysRemaining(null, Carbon::now()->addDays(12));

        $this->assertSame(12, $days);
    }

    public function test_calculate_days_remaining_returns_zero_when_expired(): void
    {
        $manager = app(SubscriptionManager::class);

        $days = $manager->calculateDaysRemaining(null, Carbon::now()->subDays(2));

        $this->assertSame(0, $days);
    }

    public function test_calculate_days_remaining_returns_null_when_no_date_is_available(): void
    {
        $manager = app(SubscriptionManager::class);

        $days = $manager->calculateDaysRemaining(null, null);

        $this->assertNull($days);
    }

    public function test_subscription_model_days_remaining_accessor_falls_back_to_ends_at(): void
    {
        $subscription = new Subscription([
            'ends_at' => Carbon::now()->addDays(15),
        ]);

        $this->assertSame(15, $subscription->days_remaining);
    }

    public function test_subscription_model_days_remaining_returns_zero_when_status_is_expired(): void
    {
        $subscription = new Subscription([
            'status' => SubscriptionStatus::EXPIRED,
            'ends_at' => Carbon::now()->addDays(15),
        ]);

        $this->assertSame(0, $subscription->days_remaining);
    }

    public function test_subscription_management_datatable_has_orderable_days_remaining_column(): void
    {
        $dataTable = app(subscriptionMangmentDataTable::class);
        $reflection = new \ReflectionMethod($dataTable, 'getColumns');
        $reflection->setAccessible(true);
        $columns = $reflection->invoke($dataTable);

        $daysCol = collect($columns)->firstWhere('data', 'days_remaining');

        $this->assertNotNull($daysCol);
        $this->assertTrue($daysCol['orderable']);
    }

    public function test_subscription_management_datatable_renders_zero_days_for_expired_subscription(): void
    {
        $dataTable = app(subscriptionMangmentDataTable::class);
        $reflection = new \ReflectionMethod($dataTable, 'addCustomColumns');
        $reflection->setAccessible(true);

        $mockDataTable = datatables()->eloquent(Subscription::query());
        $reflection->invoke($dataTable, $mockDataTable);

        $subscription = new Subscription([
            'status' => SubscriptionStatus::EXPIRED,
            'ends_at' => Carbon::now()->subDays(5),
        ]);

        $prop = new \ReflectionProperty($mockDataTable, 'columnDef');
        $prop->setAccessible(true);
        $columnDef = $prop->getValue($mockDataTable);

        $append = collect($columnDef['append'])->firstWhere('name', 'days_remaining');
        $this->assertNotNull($append);

        $closure = $append['content'];
        $output = $closure($subscription);

        $this->assertStringContainsString('0 يوم', $output);
        $this->assertStringNotContainsString('>-<', $output);
    }

    public function test_subscription_management_datatable_defines_order_column_for_days_remaining(): void
    {
        $dataTable = app(subscriptionMangmentDataTable::class);
        $reflection = new \ReflectionMethod($dataTable, 'addCustomColumns');
        $reflection->setAccessible(true);

        $mockDataTable = datatables()->eloquent(Subscription::query());
        $reflection->invoke($dataTable, $mockDataTable);

        $prop = new \ReflectionProperty($mockDataTable, 'columnDef');
        $prop->setAccessible(true);
        $columnDef = $prop->getValue($mockDataTable);

        $this->assertArrayHasKey('days_remaining', $columnDef['order']);
        $this->assertIsCallable($columnDef['order']['days_remaining']['sql']);

        // Verify SQL execution of the orderColumn closure
        $query = Subscription::query();
        $orderClosure = $columnDef['order']['days_remaining']['sql'];
        $orderClosure($query, 'desc');

        $sql = $query->toSql();
        $this->assertStringContainsString('COALESCE', $sql);
        $this->assertStringContainsString('subscription_periods', $sql);
        $this->assertStringContainsString('desc', $sql);
    }
}

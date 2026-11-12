<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use Tests\TestCase;

class AdminSubscriptionManagementFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::role('SuperAdmin')->first()
            ?? AdminUser::where('email', 'superadmin@gmail.com')->first()
            ?? AdminUser::firstOrFail();

        $this->actingAs($this->admin, 'admin');
    }

    private function createSubscriptionWithStatus(
        SubscriptionStatus $status,
        SubscriptionPeriodStatus $periodStatus,
        int $daysOffset = 30
    ): Subscription {
        $plan = Plan::first() ?? Plan::create([
            'name' => 'Test Plan',
            'price' => 100,
            'price_after_discount' => 100,
            'included_users' => 3,
            'is_active' => true,
        ]);

        $tenant = Tenant::where('id', '!=', 'test')->first() ?? Tenant::create([
            'id' => 'tenant_test_'.uniqid(),
            'company_name' => 'شركة تجريبية '.rand(100, 999),
            'commercial_number' => (string) rand(1000000000, 9999999999),
            'tax_number' => '300000000000003',
            'db_database' => 'tenant_db_'.uniqid(),
            'db_username' => 'root',
            'db_password' => 'secret_pw',
        ]);

        $endDate = now()->addDays($daysOffset);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(10),
            'ends_at' => $endDate,
            'status' => $status,
            'price' => 100,
            'total_users' => 3,
            'used_users' => 0,
            'auto_renew' => false,
        ]);

        SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'period_start' => now()->subDays(10),
            'period_end' => $endDate,
            'plan_id_snapshot' => $plan->id,
            'price_snapshot' => 100,
            'total_users_snapshot' => 3,
            'used_users_snapshot' => 0,
            'status' => $periodStatus,
            'is_current' => true,
        ]);

        return $subscription;
    }

    private function createPendingSubscription(): Subscription
    {
        return $this->createSubscriptionWithStatus(
            SubscriptionStatus::PENDING,
            SubscriptionPeriodStatus::PENDING_PAYMENT,
            30
        );
    }

    private function dataTableColumnQuery(): array
    {
        $dt = app(subscriptionMangmentDataTable::class);
        $method = new \ReflectionMethod($dt, 'getColumns');
        $method->setAccessible(true);

        return array_map(fn (array $col): array => [
            'data' => $col['data'],
            'name' => $col['name'],
            'searchable' => ($col['searchable'] ?? true) ? 'true' : 'false',
            'orderable' => ($col['orderable'] ?? true) ? 'true' : 'false',
            'search' => ['value' => '', 'regex' => 'false'],
        ], $method->invoke($dt));
    }

    public function test_expiring_page_renders_without_status_filter_and_ajax_preserves_id_and_actions(): void
    {
        $sub = $this->createSubscriptionWithStatus(SubscriptionStatus::ACTIVE, SubscriptionPeriodStatus::ACTIVE, 10);

        try {
            $page = $this->get('http://localhost/superadmin/subscription-manage/expiring');
            $page->assertStatus(200);
            $page->assertSee('اشتراكات تقارب على الانتهاء');
            $page->assertSee('subscriptions-table');
            $page->assertSee('filter-expiring');
            $page->assertDontSee('filter-sub-status');

            $response = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*',
            ])->get('http://localhost/superadmin/subscription-manage/expiring?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => $this->dataTableColumnQuery(),
                'order' => [['column' => 2, 'dir' => 'desc']],
                'created_from' => now()->subDay()->toDateString(),
                'search' => ['value' => $sub->subscription_number],
            ]));

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertArrayNotHasKey('error', $json);
            $this->assertCount(1, $json['data']);

            $row = $json['data'][0];
            $this->assertSame($sub->id, $row['id']);
            $this->assertArrayNotHasKey('db_password', $row);
            $this->assertArrayHasKey('actions', $row);
            $this->assertStringContainsString("value=\"{$sub->id}\"", $row['checkbox']);
            $this->assertStringContainsString('js-delete-btn', $row['actions']);
        } finally {
            $sub->forceDelete();
        }
    }

    public function test_expired_page_hides_expiring_filter_and_blocks_active_status_leak(): void
    {
        $activeSub = $this->createSubscriptionWithStatus(SubscriptionStatus::ACTIVE, SubscriptionPeriodStatus::ACTIVE, 20);
        $expiredSub = $this->createSubscriptionWithStatus(SubscriptionStatus::EXPIRED, SubscriptionPeriodStatus::ENDED, -5);

        try {
            $page = $this->get('http://localhost/superadmin/subscription-manage/expired');
            $page->assertStatus(200);
            $page->assertSee('الاشتراكات المنتهية');
            $page->assertDontSee('filter-expiring');
            $page->assertSee('filter-sub-status');

            $response = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*',
            ])->get('http://localhost/superadmin/subscription-manage/expired?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 50,
                'columns' => $this->dataTableColumnQuery(),
                'status' => 'active',
                'created_from' => now()->subDay()->toDateString(),
            ]));

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertArrayNotHasKey('error', $json);
            $ids = collect($json['data'])->pluck('id')->all();
            $this->assertNotContains($activeSub->id, $ids);
        } finally {
            $activeSub->forceDelete();
            $expiredSub->forceDelete();
        }
    }

    public function test_accept_and_reject_subscription_endpoints(): void
    {
        $subAccept = $this->createPendingSubscription();

        $responseAccept = $this->postJson("http://localhost/superadmin/subscription-manage/{$subAccept->id}/accept");
        $responseAccept->assertOk()->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subAccept->fresh()->status);

        $subReject = $this->createPendingSubscription();

        $responseReject = $this->postJson("http://localhost/superadmin/subscription-manage/{$subReject->id}/reject", [
            'reject_reason' => 'رفض تجريبي للاختبار',
        ]);
        $responseReject->assertOk()->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::CANCELLED, $subReject->fresh()->status);

        $subAccept->forceDelete();
        $subReject->forceDelete();
    }

    public function test_mass_accept_and_mass_reject_subscription_endpoints(): void
    {
        $sub1 = $this->createPendingSubscription();
        $sub2 = $this->createPendingSubscription();

        $responseAccept = $this->postJson('http://localhost/superadmin/subscription-manage/mass-accept', [
            'ids' => [$sub1->id, $sub2->id],
        ]);
        $responseAccept->assertOk()->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::ACTIVE, $sub1->fresh()->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $sub2->fresh()->status);

        $sub3 = $this->createPendingSubscription();
        $sub4 = $this->createPendingSubscription();

        $responseReject = $this->postJson('http://localhost/superadmin/subscription-manage/mass-reject', [
            'ids' => [$sub3->id, $sub4->id],
            'reject_reason' => 'رفض جماعي للاختبار',
        ]);
        $responseReject->assertOk()->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::CANCELLED, $sub3->fresh()->status);
        $this->assertSame(SubscriptionStatus::CANCELLED, $sub4->fresh()->status);

        $sub1->forceDelete();
        $sub2->forceDelete();
        $sub3->forceDelete();
        $sub4->forceDelete();
    }

    protected function tearDown(): void
    {
        Subscription::where('status', SubscriptionStatus::CANCELLED)->forceDelete();
        Subscription::where('status', SubscriptionStatus::PENDING)->forceDelete();

        // Ensure the test tenant always has an active subscription
        $testSub = Subscription::where('tenant_id', 'test')->latest('id')->first();
        if ($testSub && $testSub->status !== SubscriptionStatus::ACTIVE) {
            $testSub->update(['status' => SubscriptionStatus::ACTIVE]);
        }

        parent::tearDown();
    }
}

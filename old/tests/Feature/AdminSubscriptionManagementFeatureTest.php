<?php

declare(strict_types=1);

namespace Tests\Feature;

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

        $this->admin = AdminUser::where('email', 'superadmin@gmail.com')->first();
        if (! $this->admin) {
            $this->admin = AdminUser::firstOrCreate(
                ['email' => 'admin_sub_test@levan.com'],
                [
                    'name' => 'Sub Admin Test',
                    'password' => bcrypt('password'),
                    'phone' => '0500000098',
                    'status' => 'active',
                ]
            );
        }

        $this->admin->givePermissionTo([
            'subscriptions.pending.view',
            'subscriptions.pending.approve',
            'subscriptions.pending.reject',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    private function createPendingSubscription(): Subscription
    {
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
            'db_password' => '',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => SubscriptionStatus::PENDING,
            'price' => 100,
            'total_users' => 3,
            'used_users' => 0,
            'auto_renew' => false,
        ]);

        SubscriptionPeriod::create([
            'subscription_id' => $subscription->id,
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'plan_id_snapshot' => $plan->id,
            'price_snapshot' => 100,
            'total_users_snapshot' => 3,
            'used_users_snapshot' => 0,
            'status' => SubscriptionPeriodStatus::PENDING_PAYMENT,
            'is_current' => true,
        ]);

        return $subscription;
    }

    public function test_pending_page_renders_successfully_with_stats_and_datatable(): void
    {
        $this->createPendingSubscription();

        $response = $this->get('http://localhost/admin/subscription-manage/pending');

        $response->assertStatus(200);
        $response->assertSee('طلبات الاشتراك الجديدة');
        $response->assertSee('subscriptions-table');
        $response->assertSee('إجمالي الاشتراكات');
        $response->assertSee('طلبات جديدة معلقة');
    }

    public function test_pending_datatable_ajax_returns_json_data_without_errors(): void
    {
        $sub = $this->createPendingSubscription();

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json, text/javascript, */*',
        ])->get('http://localhost/admin/subscription-manage/pending?draw=1&start=0&length=10');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $json = $response->json();
        $this->assertArrayHasKey('draw', $json);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayNotHasKey('error', $json);
        $this->assertNotEmpty($json['data']);
    }

    public function test_pending_datatable_search_works_without_sql_errors(): void
    {
        $sub = $this->createPendingSubscription();

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json, text/javascript, */*',
        ])->get('http://localhost/admin/subscription-manage/pending?draw=1&start=0&length=10&search[value]='.urlencode($sub->subscription_number));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json);
    }

    public function test_accept_and_reject_subscription_endpoints(): void
    {
        $subAccept = $this->createPendingSubscription();

        $responseAccept = $this->postJson("http://localhost/admin/subscription-manage/{$subAccept->id}/accept");
        $responseAccept->assertOk()->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::ACTIVE, $subAccept->fresh()->status);

        $subReject = $this->createPendingSubscription();

        $responseReject = $this->postJson("http://localhost/admin/subscription-manage/{$subReject->id}/reject", [
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

        $responseAccept = $this->postJson("http://localhost/admin/subscription-manage/mass-accept", [
            'ids' => [$sub1->id, $sub2->id],
        ]);
        $responseAccept->assertOk()->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::ACTIVE, $sub1->fresh()->status);
        $this->assertSame(SubscriptionStatus::ACTIVE, $sub2->fresh()->status);

        $sub3 = $this->createPendingSubscription();
        $sub4 = $this->createPendingSubscription();

        $responseReject = $this->postJson("http://localhost/admin/subscription-manage/mass-reject", [
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

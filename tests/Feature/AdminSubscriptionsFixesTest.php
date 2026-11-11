<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTables\Admin\Subscriptions\SubscriptionDataTable;
use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminSubscriptionsFixesTest extends TestCase
{
    private AdminUser $admin;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::where('email', 'superadmin@gmail.com')->first()
            ?? AdminUser::firstOrFail();

        $this->city = City::firstOrFail();
        $this->actingAs($this->admin, 'admin');
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'id' => 'subfix_'.uniqid(),
            'company_name' => 'شركة اختبار الاشتراكات '.rand(100, 999),
            'commercial_number' => (string) rand(1000000000, 9999999999),
            'tax_number' => (string) rand(100000000000000, 999999999999999),
            'city_id' => $this->city->id,
            'address' => 'الرياض',
            'db_database' => 'subfix_db_'.uniqid(),
            'db_username' => 'root',
            'db_password' => 'secret_test_pw',
        ], $overrides));
    }

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'باقة اختبار '.uniqid(),
            'code' => 'SUBFIX-'.uniqid(),
            'price' => 100.00,
            'discount_percentage' => 0,
            'subscription_duration_type' => 'limited',
            'subscription_billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'included_users' => 5,
            'properties_limit' => 20,
            'storage_quota_gb' => 10,
            'active_auctions_limit' => 5,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_subscription_relations_resolve_soft_deleted_tenant_and_plan(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'auto_renew' => false,
        ]);

        try {
            $tenant->delete();
            Plan::whereKey($plan->id)->update(['deleted_at' => now()]);

            $fresh = $subscription->fresh(['tenant', 'plan']);
            $this->assertNotNull($fresh->tenant);
            $this->assertSame($tenant->company_name, $fresh->tenant->company_name);
            $this->assertNotNull($fresh->plan);
            $this->assertSame($plan->name, $fresh->plan->name);
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_toggle_status_rejects_expired_and_cancelled_subscriptions_with_422(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::EXPIRED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
            'auto_renew' => false,
        ]);

        try {
            $response = $this->patchJson("http://localhost/superadmin/subscriptions/{$subscription->id}/toggle-status");

            $response->assertStatus(422)->assertJson(['success' => false]);
            $this->assertSame(SubscriptionStatus::EXPIRED, $subscription->fresh()->status);

            $subscription->update(['status' => SubscriptionStatus::ACTIVE]);
            $okResponse = $this->patchJson("http://localhost/superadmin/subscriptions/{$subscription->id}/toggle-status");
            $okResponse->assertOk()->assertJson([
                'success' => true,
                'status' => 'suspended',
                'is_active' => false,
            ]);
            $this->assertSame(SubscriptionStatus::SUSPENDED, $subscription->fresh()->status);
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_expiring_soon_count_includes_active_subscriptions_without_periods(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $service = app(AdminSubscriptionService::class);
        $beforeCount = $service->getExpiringSoonCount();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        try {
            $this->assertSame($beforeCount + 1, $service->getExpiringSoonCount());
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_mass_delete_subscription_clears_tenant_and_sidebar_caches_and_sidebar_counts_cancelled_as_expired(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::CANCELLED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'auto_renew' => false,
        ]);

        try {
            Cache::put("tenant:{$tenant->id}:subscription", 'cached', 60);
            Cache::put('admin:expiring_subscriptions_count', 99, 60);
            Cache::put('admin:expired_subscriptions_count', 99, 60);

            $res = $this->postJson('http://localhost/superadmin/mass-delete', [
                'model' => 'Subscription',
                'ids' => (string) $subscription->id,
            ]);
            $res->assertOk()->assertJson(['success' => true]);

            $this->assertNull(Cache::get("tenant:{$tenant->id}:subscription"));
            $this->assertNull(Cache::get('admin:expiring_subscriptions_count'));
            $this->assertNull(Cache::get('admin:expired_subscriptions_count'));

            // Verify sidebar menu view counts CANCELLED in expired badge when rendered fresh
            $subCancelled = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'price' => 100.00,
                'status' => SubscriptionStatus::CANCELLED,
                'total_users' => 5,
                'used_users' => 0,
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->subDays(1),
                'auto_renew' => false,
            ]);

            $menuHtml = view('admin.layouts.sections.menu.verticalMenu')->render();
            $this->assertMatchesRegularExpression(
                '/data-sidebar-counter="subscriptions_expired">\s*[1-9]\d*\s*</',
                $menuHtml
            );

            $subCancelled->forceDelete();
        } finally {
            Bill::where('tenant_id', $tenant->id)->forceDelete();
            Subscription::where('tenant_id', $tenant->id)->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_subscription_datatable_preserves_id_and_handles_created_at_when_sorted_by_relations(): void
    {
        $tenant = $this->makeTenant(['company_name' => 'ZZZ Unique Sub Company']);
        $plan = $this->makePlan(['name' => 'ZZZ Unique Sub Plan']);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::EXPIRED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
            'auto_renew' => false,
        ]);

        try {
            $dt = app(SubscriptionDataTable::class);
            $colsMethod = new \ReflectionMethod($dt, 'getColumns');
            $colsMethod->setAccessible(true);
            $reqCols = array_map(fn (array $c): array => [
                'data' => $c['data'],
                'name' => $c['name'],
                'searchable' => ($c['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => ($c['orderable'] ?? true) ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ], $colsMethod->invoke($dt));

            foreach ([2, 3, 4] as $colIndex) {
                $response = $this->withHeaders([
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Accept' => 'application/json',
                ])->get('http://localhost/superadmin/subscriptions?'.http_build_query([
                    'draw' => 1,
                    'start' => 0,
                    'length' => 10,
                    'columns' => $reqCols,
                    'order' => [['column' => $colIndex, 'dir' => 'desc']],
                    'search' => ['value' => $subscription->subscription_number],
                    'created_from' => now()->subDay()->toDateString(),
                    'created_to' => now()->addDay()->toDateString(),
                ]));

                $response->assertOk();
                $json = $response->json();
                $this->assertArrayNotHasKey('error', $json);
                $this->assertCount(1, $json['data']);

                $row = $json['data'][0];
                $this->assertSame($subscription->id, $row['id']);
                $this->assertArrayNotHasKey('db_password', $row);
                $this->assertArrayNotHasKey('api_token', $row);
                $this->assertStringContainsString('منتهي', $row['status']);
                $this->assertStringNotContainsString('js-table-status-toggle', $row['status']);
            }
        } finally {
            $subscription->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_subscription_datatable_expiring_filter_only_includes_active_subscriptions(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        $plan = $this->makePlan();

        $activeExpiring = Subscription::create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        $cancelledSub = Subscription::create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 100.00,
            'status' => SubscriptionStatus::CANCELLED,
            'total_users' => 5,
            'used_users' => 0,
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'auto_renew' => false,
        ]);

        try {
            $dt = app(SubscriptionDataTable::class);
            $colsMethod = new \ReflectionMethod($dt, 'getColumns');
            $colsMethod->setAccessible(true);
            $reqCols = array_map(fn (array $c): array => [
                'data' => $c['data'],
                'name' => $c['name'],
                'searchable' => ($c['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => ($c['orderable'] ?? true) ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ], $colsMethod->invoke($dt));

            $response = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])->get('http://localhost/superadmin/subscriptions?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 100,
                'columns' => $reqCols,
                'expiring_in' => '30',
            ]));

            $response->assertOk();
            $ids = collect($response->json('data'))->pluck('id')->all();
            $this->assertContains($activeExpiring->id, $ids);
            $this->assertNotContains($cancelledSub->id, $ids);
        } finally {
            $activeExpiring->forceDelete();
            $cancelledSub->forceDelete();
            Tenant::withTrashed()->whereKey($tenantA->id)->forceDelete();
            Tenant::withTrashed()->whereKey($tenantB->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_create_page_omits_sensitive_tenant_secrets_duplicate_scripts_and_preselects_company(): void
    {
        $tenant = $this->makeTenant([
            'db_password' => 'secret-pw-leak-999',
            'db_username' => 'secret-user-leak-999',
            'api_token' => 'secret-token-leak-999',
        ]);

        try {
            $response = $this->get('http://localhost/superadmin/subscriptions/create?company_id='.$tenant->id);

            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringNotContainsString('secret-pw-leak-999', $html);
            $this->assertStringNotContainsString('secret-user-leak-999', $html);
            $this->assertStringNotContainsString('secret-token-leak-999', $html);
            $this->assertSame(
                1,
                preg_match_all('/<script[^>]*src="[^"]*form-validation[^"]*"/i', $html)
            );
            $this->assertStringNotContainsString('payment_proof', $html);
            $this->assertMatchesRegularExpression(
                '/<option[^>]*value="'.preg_quote($tenant->id, '/').'"[^>]*selected/s',
                $html
            );
        } finally {
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
        }
    }

    public function test_wizard_validate_step_two_does_not_require_duplicate_payment_proof_field(): void
    {
        $response = $this->postJson('http://localhost/superadmin/wizard-drafts/validate-step', [
            'target_path' => '/superadmin/subscriptions',
            'target_method' => 'POST',
            'fields' => [
                'start_date',
                'end_date',
                'period_months',
                'coupon_code',
                'payment_method',
                'payment_status',
                'payment_type',
                'transaction_id',
                'receipt_upload',
                'payment_proof',
            ],
            'start_date' => '2026-11-11',
            'end_date' => '2026-12-11',
            'period_months' => 1,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'completed',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receipt_upload'])
            ->assertJsonMissingValidationErrors(['payment_proof']);
    }

    public function test_validate_coupon_endpoint_returns_discount_math_for_valid_coupon_and_422_for_invalid(): void
    {
        $plan = $this->makePlan([
            'price' => 100.00,
            'discount_percentage' => 0,
            'subscription_billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'is_active' => true,
        ]);

        $coupon = Coupon::create([
            'name' => 'خصم اختبار',
            'code' => 'FIX20_'.uniqid(),
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'expiry_date' => now()->addDay()->toDateString(),
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        try {
            $okResponse = $this->postJson('http://localhost/superadmin/subscriptions/validate-coupon', [
                'plan_id' => $plan->id,
                'period_months' => 6,
                'coupon_code' => $coupon->code,
            ]);

            $okResponse->assertOk()
                ->assertJson([
                    'success' => true,
                    'coupon_code' => $coupon->code,
                    'original_total' => 600.0,
                    'discount_amount' => 120.0,
                    'final_total' => 480.0,
                ]);

            $badResponse = $this->postJson('http://localhost/superadmin/subscriptions/validate-coupon', [
                'plan_id' => $plan->id,
                'period_months' => 6,
                'coupon_code' => 'NONEXISTENT-999',
            ]);

            $badResponse->assertStatus(422);
        } finally {
            $coupon->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_create_subscription_scales_period_applies_coupon_respects_pending_status_and_records_transaction_metadata(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan([
            'price' => 100.00,
            'discount_percentage' => 0,
            'subscription_billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'is_active' => true,
        ]);
        $coupon = Coupon::create([
            'name' => 'خصم انشاء اشتراك',
            'code' => 'SUB20_'.uniqid(),
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'expiry_date' => now()->addDay()->toDateString(),
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $createdSub = null;
        try {
            $service = app(AdminSubscriptionService::class);

            try {
                $service->createSubscription($tenant, [
                    'plan_id' => $plan->id,
                    'start_date' => '2026-11-11',
                    'period_months' => 6,
                    'coupon_code' => 'INVALID-COUPON-CODE',
                    'payment_method' => 'bank_transfer',
                    'payment_status' => 'pending',
                ]);
                $this->fail('Expected ValidationException for invalid coupon_code.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('coupon_code', $e->errors());
            }

            $createdSub = $service->createSubscription($tenant, [
                'plan_id' => $plan->id,
                'start_date' => '2026-11-11',
                'period_months' => 6,
                'coupon_code' => $coupon->code,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'transaction_id' => 'TRX-999',
                'admin_notes' => 'note',
            ]);

            $createdSub->refresh();
            $period = $createdSub->currentPeriod;
            $bill = Bill::where('subscription_id', $createdSub->id)->firstOrFail();

            $this->assertSame(480.0, (float) $createdSub->price);
            $this->assertSame(SubscriptionStatus::SUSPENDED, $createdSub->status);
            $this->assertSame('2027-05-11', $createdSub->ends_at->format('Y-m-d'));
            $this->assertNotNull($period);
            $this->assertSame(SubscriptionPeriodStatus::PENDING_PAYMENT, $period->status);

            $this->assertSame(480.0, (float) $bill->amount);
            $this->assertSame(600.0, (float) $bill->original_amount);
            $this->assertSame(120.0, (float) $bill->discount_amount);
            $this->assertSame(BillStatus::UNPAID, $bill->status);
            $this->assertNull($bill->paid_at);
            $this->assertSame('note', $bill->transfer_notes);
            $this->assertSame('TRX-999', $bill->metadata['transaction_id'] ?? null);
            $this->assertSame('note', $bill->metadata['admin_notes'] ?? null);
            $this->assertSame($coupon->id, $bill->coupon_id);
            $this->assertSame($coupon->code, $bill->coupon_code);
            $this->assertSame(1, $coupon->fresh()->used_count);
        } finally {
            if ($createdSub) {
                LicenseSeat::where('subscription_id', $createdSub->id)->delete();
                Bill::withTrashed()->where('subscription_id', $createdSub->id)->forceDelete();
                SubscriptionPeriod::withTrashed()->where('subscription_id', $createdSub->id)->forceDelete();
                $createdSub->forceDelete();
            }
            $coupon->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_client_show_renders_subscription_scripts_and_handles_expired_days_and_periodless_dates(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan(['is_active' => true]);

        $expiredSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'subscription_number' => 'SUB-EXP-'.uniqid(),
            'billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'price' => 100.00,
            'status' => SubscriptionStatus::EXPIRED,
            'total_users' => 1,
            'used_users' => 0,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(10),
        ]);

        $activePeriodlessSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'subscription_number' => 'SUB-ACT-'.uniqid(),
            'billing_cycle' => SubscriptionBillingCycle::MONTHLY,
            'price' => 100.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 1,
            'used_users' => 0,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(20),
        ]);

        try {
            $response = $this->get('http://localhost/superadmin/clients/'.$tenant->id);
            $response->assertOk();

            $html = $response->getContent();

            $this->assertStringContainsString('window.confirmRecordDelete', $html);
            $this->assertStringContainsString("$(document).on('change', '.js-sub-toggle'", $html);
            $this->assertStringContainsString('يوماً', $html);
            $this->assertSame(1, substr_count($html, 'js-sub-toggle text-center'));
            $this->assertMatchesRegularExpression('/<span class="badge bg-label-danger">منتهي<\/span>/', $html);
        } finally {
            $expiredSub->forceDelete();
            $activePeriodlessSub->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }

    public function test_plan_show_renders_subscription_starts_at_and_ends_at_dates(): void
    {
        $tenant = $this->makeTenant();
        $plan = $this->makePlan(['is_active' => true]);

        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'subscription_number' => 'SUB-PLN-'.uniqid(),
            'billing_cycle' => SubscriptionBillingCycle::YEARLY,
            'price' => 500.00,
            'status' => SubscriptionStatus::ACTIVE,
            'total_users' => 1,
            'used_users' => 0,
            'starts_at' => '2026-06-15 10:00:00',
            'ends_at' => '2027-06-15 10:00:00',
        ]);

        try {
            $response = $this->get('http://localhost/superadmin/plans/'.$plan->id);
            $response->assertOk();

            $response->assertSee('15-06-2026', false);
            $response->assertSee('15-06-2027', false);
        } finally {
            $sub->forceDelete();
            Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            Plan::withTrashed()->whereKey($plan->id)->forceDelete();
        }
    }
}

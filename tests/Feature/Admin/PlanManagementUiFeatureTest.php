<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DataTables\Admin\Plans\PlanDataTable;
use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class PlanManagementUiFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::first() ?? AdminUser::factory()->create();
    }

    public function test_can_create_plan_with_unlimited_users_and_limited_operational_limits(): void
    {
        $code = 'TEST_UNLTD_USERS_'.uniqid();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.plans.store'), [
                'name' => 'باقة مستخدمين غير محدودة',
                'code' => $code,
                'plan_type' => PlanType::PROFESSIONAL->value,
                'color' => PlanColor::PRIMARY->value,
                'price' => 1500,
                'subscription_billing_cycle' => SubscriptionBillingCycle::MONTHLY->value,
                'is_active' => true,
                'features' => ['ميزة 1', 'ميزة 2'],
                'users_limit_mode' => 'unlimited',
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'properties_limit' => 20,
                'storage_quota_gb' => 10,
                'active_auctions_limit' => 5,
            ]);

        $response->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('code', $code)->firstOrFail();
        $this->assertTrue($plan->hasUnlimitedUsers());
        $this->assertNull($plan->included_users);
        $this->assertNull($plan->extra_user_license_price);
        $this->assertFalse($plan->isUnlimited());
        $this->assertSame(20, $plan->properties_limit);
        $this->assertSame(5, $plan->active_auctions_limit);
    }

    public function test_can_create_plan_with_limited_users_and_unlimited_operational_limits(): void
    {
        $code = 'TEST_LTD_USERS_'.uniqid();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.plans.store'), [
                'name' => 'باقة عمليات غير محدودة',
                'code' => $code,
                'plan_type' => PlanType::ENTERPRISE->value,
                'color' => PlanColor::SECONDARY->value,
                'price' => 2500,
                'subscription_billing_cycle' => SubscriptionBillingCycle::YEARLY->value,
                'is_active' => true,
                'features' => ['ميزة أ', 'ميزة ب'],
                'users_limit_mode' => 'limited',
                'included_users' => 8,
                'extra_user_license_price' => 75,
                'limits_mode' => PlanLimitsMode::UNLIMITED->value,
            ]);

        $response->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('code', $code)->firstOrFail();
        $this->assertFalse($plan->hasUnlimitedUsers());
        $this->assertSame(8, $plan->included_users);
        $this->assertEquals(75.0, (float) $plan->extra_user_license_price);
        $this->assertTrue($plan->isUnlimited());
        $this->assertNull($plan->properties_limit);
        $this->assertNull($plan->active_auctions_limit);
    }

    public function test_plan_datatable_renders_cleanly_with_consolidated_columns(): void
    {
        $this->actingAs($this->admin, 'admin');

        $dataTable = app(PlanDataTable::class);
        $response = $dataTable->ajax();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);

        if (! empty($data['data'])) {
            $firstRow = $data['data'][0];
            $this->assertArrayHasKey('color', $firstRow);
            $this->assertArrayHasKey('code', $firstRow);
            $this->assertArrayHasKey('name', $firstRow);
            $this->assertArrayHasKey('is_most_requested', $firstRow);
            $this->assertArrayHasKey('plan_type', $firstRow);
            $this->assertArrayHasKey('price', $firstRow);
            $this->assertArrayHasKey('duration', $firstRow);
            $this->assertArrayHasKey('included_users', $firstRow);
            $this->assertArrayHasKey('extra_user_license_price', $firstRow);
            $this->assertArrayHasKey('limits_summary', $firstRow);
            $this->assertArrayHasKey('is_active', $firstRow);
            $this->assertArrayHasKey('actions', $firstRow);
        }

        // Verify formatting rules:
        // 1) Unlimited badges must not contain ti-infinity and must use bg-label-success
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('ti-infinity', $content);
        $this->assertStringContainsString('bg-label-success', $content);

        // 2) Extra user license price is only number, no "/ مستخدم إضافي"
        $this->assertStringNotContainsString('/ مستخدم إضافي', $content);

        // 3) Operational limits secondary badges on same line (text-nowrap)
        $this->assertStringContainsString('bg-label-secondary', $content);
        $this->assertStringContainsString('text-nowrap', $content);
    }
}

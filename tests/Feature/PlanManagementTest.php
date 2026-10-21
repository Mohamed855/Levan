<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Plans\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_public_plan(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.plans.store'), [
                'name' => 'باقة الاختبار',
                'code' => 'TEST_PLAN',
                'plan_type' => 'professional',
                'color' => 'blue',
                'price' => 500,
                'discount_percentage' => 10,
                'limits_mode' => 'limited',
                'included_users' => 5,
                'properties_limit' => 20,
                'storage_quota_gb' => 10,
                'active_auctions_limit' => 2,
                'subscription_billing_cycle' => 'monthly',
                'features_text' => "ميزة أولى\nميزة ثانية",
                'is_active' => 1,
            ])
            ->assertRedirect();

        $plan = Plan::firstOrFail();

        $this->assertSame(['ميزة أولى', 'ميزة ثانية'], $plan->features);
        $this->get(route('site.index'))->assertOk()->assertSee('باقة الاختبار');
    }

public function test_plan_create_form_supports_restorable_drafts(): void
{
    $admin = AdminUser::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.plans.create'))
        ->assertOk()
        ->assertSee('data-wizard-resource="admin.plans.create"', false)
        ->assertSee('data-save-draft', false);
}}

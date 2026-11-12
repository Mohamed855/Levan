<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PlanCreationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        DB::table('admin_users')->insert(['id' => 1, 'name' => 'Plan administrator']);
        (require database_path('migrations/admin/2026_09_23_000015_create_plans_table.php'))->up();

        $admin = (new AdminUser)->forceFill([
            'id' => 1,
            'name' => 'Plan administrator',
            'status' => 'active',
            'is_super_admin' => true,
        ]);
        $admin->setRelation('roles', collect([(new Role)->forceFill(['name' => 'SuperAdmin', 'guard_name' => 'admin'])]));
        $this->actingAs($admin, 'admin');
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    public function test_plan_creation_accepts_blank_optional_prices(): void
    {
        $this->post(route('admin.plans.store'), $this->payload([
            'yearly_renewal_price' => '',
            'discount_percentage' => '',
        ]))->assertRedirect(route('admin.plans.index'))->assertSessionHasNoErrors();

        $plan = Plan::sole();
        self::assertSame('0.00', $plan->yearly_renewal_price);
        self::assertSame(0.0, $plan->discount_percentage);
        self::assertSame('50.00', $plan->extra_user_license_price);
        self::assertSame(1, $plan->created_by);
    }

    public function test_unlimited_plan_creation_accepts_blank_extra_user_price(): void
    {
        $this->post(route('admin.plans.store'), $this->payload([
            'limits_mode' => 'unlimited',
            'extra_user_license_price' => '',
        ]))->assertRedirect(route('admin.plans.index'))->assertSessionHasNoErrors();

        $plan = Plan::sole();
        self::assertSame('0.00', $plan->extra_user_license_price);
        self::assertNull($plan->included_users);
        self::assertNull($plan->properties_limit);
        self::assertNull($plan->storage_quota_gb);
        self::assertNull($plan->active_auctions_limit);
    }

    public function test_plan_creation_preserves_explicit_optional_prices(): void
    {
        $this->post(route('admin.plans.store'), $this->payload([
            'yearly_renewal_price' => '950.50',
            'discount_percentage' => '12.5',
            'extra_user_license_price' => '25.75',
        ]))->assertRedirect(route('admin.plans.index'))->assertSessionHasNoErrors();

        $plan = Plan::sole();
        self::assertSame('950.50', $plan->yearly_renewal_price);
        self::assertSame(12.5, $plan->discount_percentage);
        self::assertSame('25.75', $plan->extra_user_license_price);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'New plan',
            'code' => 'NEW_PLAN',
            'plan_type' => 'basic',
            'color' => 'primary',
            'price' => '100',
            'discount_percentage' => '0',
            'subscription_billing_cycle' => 'monthly',
            'is_active' => '1',
            'limits_mode' => 'limited',
            'included_users' => '5',
            'properties_limit' => '20',
            'storage_quota_gb' => '10',
            'active_auctions_limit' => '5',
            'extra_user_license_price' => '50',
            'features' => ['Auction management'],
        ], $overrides);
    }
}

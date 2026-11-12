<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\Plans\PlanController;
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
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id');
            $table->string('status')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('subscription_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id');
            $table->string('status')->nullable();
            $table->dateTime('period_end')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->softDeletes();
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

    public function test_plan_create_passes_generated_code(): void
    {
        $view = app(PlanController::class)->create();
        self::assertTrue(isset($view['generatedCode']));
        $code = $view['generatedCode'];
        self::assertIsString($code);
        self::assertSame(8, strlen($code));
        self::assertMatchesRegularExpression('/^[A-Z][A-Z0-9_]*$/', $code);
    }

    public function test_plan_code_cannot_be_modified_on_update(): void
    {
        $this->post(route('admin.plans.store'), $this->payload([
            'code' => 'INITIAL_CODE',
        ]))->assertRedirect(route('admin.plans.index'))->assertSessionHasNoErrors();

        $plan = Plan::sole();
        self::assertSame('INITIAL_CODE', $plan->code);

        $this->put(route('admin.plans.update', $plan->id), $this->payload([
            'name' => 'Updated Plan Name',
            'code' => 'ATTEMPTED_NEW_CODE',
        ]))->assertRedirect(route('admin.plans.index'))->assertSessionHasNoErrors();

        $plan->refresh();
        self::assertSame('Updated Plan Name', $plan->name);
        self::assertSame('INITIAL_CODE', $plan->code);
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

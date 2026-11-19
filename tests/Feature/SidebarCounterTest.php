<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Users\AdminUserStatus;
use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin;
use App\Http\Middleware\EnsureInitialPasswordChanged;
use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\TenantMaintenance;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Sidebar\SidebarCounterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class SidebarCounterTest extends TestCase
{
    private SidebarCounterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [],
            'activitylog.enabled' => false,
            'dashboard-notifications.enabled' => true,
        ]);

        DB::purge('mysql');
        DB::purge('tenant');

        $this->service = app(SidebarCounterService::class);

        // Set up admin tables in mysql memory DB
        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->default('admin@example.com');
            $table->string('password')->default('secret');
            $table->string('status')->default('active');
            $table->boolean('is_super_admin')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Basic');
            $table->boolean('is_trial')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->default('DISCOUNT');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('bill_number')->default('INV-001');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('trial_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        // Set up tenant tables in both connections for standalone unit testing
        foreach (['tenant', 'mysql'] as $conn) {
            Schema::connection($conn)->create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });

            Schema::connection($conn)->create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->default('Tenant User');
                $table->string('email')->default('tenant@example.com');
                $table->string('password')->default('secret');
                $table->string('status')->default('active');
                $table->string('global_uid')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::connection($conn)->create('properties', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->default('Property 1');
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::connection($conn)->create('assignments', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->default('Assignment 1');
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::connection($conn)->create('auctions', function (Blueprint $table): void {
                $table->id();
                $table->string('title')->default('Auction 1');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_admin_sidebar_counts_include_archive_and_subscriptions(): void
    {
        Cache::flush();

        // Initially 0
        $counts = $this->service->getAdminCounts();
        $this->assertSame(0, $counts['archive_admin_total']);
        $this->assertSame(0, $counts['archive_admin_users']);
        $this->assertSame(0, $counts['archive_admin_tenants']);
        $this->assertSame(0, $counts['archive_admin_plans']);
        $this->assertSame(0, $counts['archive_admin_coupons']);
        $this->assertSame(0, $counts['archive_admin_bills']);

        // Soft delete 1 of each
        $admin = AdminUser::create(['name' => 'Archived User']);
        $admin->delete();

        $tenant = Tenant::create(['id' => 't1']);
        $tenant->delete();

        $plan = Plan::create(['name' => 'Archived Plan', 'is_trial' => false]);
        $plan->delete();

        $coupon = Coupon::create(['code' => 'ARCHIVED']);
        $coupon->delete();

        $bill = Bill::create(['bill_number' => 'INV-ARCHIVED']);
        $bill->delete();

        $this->service->clearAdminArchiveCache();

        $updatedCounts = $this->service->getAdminCounts();

        $this->assertSame(1, $updatedCounts['archive_admin_users']);
        $this->assertSame(1, $updatedCounts['archive_admin_tenants']);
        $this->assertSame(1, $updatedCounts['archive_admin_plans']);
        $this->assertSame(1, $updatedCounts['archive_admin_coupons']);
        $this->assertSame(1, $updatedCounts['archive_admin_bills']);
        $this->assertSame(5, $updatedCounts['archive_admin_total']);
    }

    public function test_tenant_sidebar_counts_include_archive_items(): void
    {
        Cache::flush();

        // Create tenant model
        $tenant = Tenant::create(['id' => 'unit-test-tenant']);
        tenancy()->initialize($tenant);

        // Initially 0
        $counts = $this->service->getTenantCounts();
        $this->assertSame(0, $counts['archive_tenant_total']);
        $this->assertSame(0, $counts['archive_tenant_users']);
        $this->assertSame(0, $counts['archive_tenant_properties']);
        $this->assertSame(0, $counts['archive_tenant_assignments']);
        $this->assertSame(0, $counts['archive_tenant_auctions']);

        // Soft delete 1 user, 2 properties, 1 assignment, 1 auction
        $user = User::create(['name' => 'Archived User']);
        $user->delete();

        $p1 = Property::create(['name' => 'Prop 1']);
        $p1->delete();
        $p2 = Property::create(['name' => 'Prop 2']);
        $p2->delete();

        $assignment = Assignment::create(['name' => 'Assign 1']);
        $assignment->delete();

        $auction = Auction::create(['title' => 'Auc 1']);
        $auction->delete();

        $this->service->clearTenantArchiveCache('unit-test-tenant');

        $updatedCounts = $this->service->getTenantCounts();

        $this->assertSame(1, $updatedCounts['archive_tenant_users']);
        $this->assertSame(2, $updatedCounts['archive_tenant_properties']);
        $this->assertSame(1, $updatedCounts['archive_tenant_assignments']);
        $this->assertSame(1, $updatedCounts['archive_tenant_auctions']);
        $this->assertSame(5, $updatedCounts['archive_tenant_total']);

        tenancy()->end();
    }

    public function test_feed_endpoint_returns_realtime_archive_counters(): void
    {
        Cache::flush();

        $this->withoutMiddleware([
            EnsureAdminTwoFactorIsEnforced::class,
            EnsureInitialPasswordChanged::class,
            EnsureAuthenticatedAndActiveAdmin::class,
        ]);

        Schema::connection('mysql')->create('dashboard_notification_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scope');
            $table->string('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('payload');
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        $admin = AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'status' => AdminUserStatus::ACTIVE,
            'is_super_admin' => true,
        ]);
        $plan = Plan::create(['name' => 'Archived Plan', 'is_trial' => false]);
        $plan->delete();

        $this->service->clearAdminArchiveCache();

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.dashboard-notifications.feed'));

        $response->assertOk()
            ->assertJsonPath('sidebar_counts.archive_admin_plans', 1)
            ->assertJsonPath('sidebar_counts.archive_admin_total', 1);
    }

    public function test_tenant_feed_endpoint_returns_realtime_archive_counters(): void
    {
        Cache::flush();

        $this->withoutMiddleware([
            EnsureTenantTwoFactorIsEnforced::class,
            TenantMaintenance::class,
        ]);

        $tenant = Tenant::create(['id' => 'feed-test-tenant']);
        tenancy()->initialize($tenant);
        DB::setDefaultConnection('tenant');
        foreach ([
            '001_create_permissions_table',
            '002_create_roles_table',
            '003_create_model_has_permissions_table',
            '004_create_model_has_roles_table',
            '005_create_role_has_permissions_table',
        ] as $migration) {
            (require database_path('migrations/tenant/2026_09_24_000007_'.$migration.'.php'))->up();
        }

        $user = User::create([
            'name' => 'Active Tenant User',
            'email' => 'active@tenant.com',
            'status' => 'active',
        ]);
        $user->givePermissionTo(Permission::create(['name' => 'properties_archive.view', 'guard_name' => 'tenant']));

        $property = Property::create(['name' => 'Archived Prop']);
        $property->delete();

        $this->service->clearTenantArchiveCache('feed-test-tenant');

        $response = $this->actingAs($user, 'tenant')
            ->getJson(tenant_route_name('dashboard-notifications.feed'));

        $response->assertOk()
            ->assertJsonPath('sidebar_counts.archive_tenant_properties', 1)
            ->assertJsonPath('sidebar_counts.archive_tenant_total', 1)
            ->assertJsonPath('sidebar_counts.pending_auctions', 0);

        tenancy()->end();
    }
}

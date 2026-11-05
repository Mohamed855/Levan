<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Dashboard\DashboardService as AdminDashboardService;
use App\Services\Tenants\Dashboard\DashboardService as TenantDashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

class TestOverviewTenancyBootstrapper implements TenancyBootstrapper
{
    public static array $databases = [];
    public static string $defaultDb = '';

    public function bootstrap(\Stancl\Tenancy\Contracts\Tenant $tenant): void
    {
        $db = static::$databases[$tenant->getTenantKey()] ?? static::$defaultDb;
        config(['database.connections.tenant.database' => $db]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $pdo = DB::connection('tenant')->getPdo();
        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                if (! $date) return null;
                $timestamp = strtotime((string) $date);
                if (! $timestamp) return null;
                $phpFormat = str_replace(['%Y', '%m', '%d', '%H', '%i', '%s'], ['Y', 'm', 'd', 'H', 'i', 's'], (string) $format);
                return date($phpFormat, $timestamp);
            }, 2);
        }
    }

    public function revert(): void
    {
        DB::purge('tenant');
        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

final class OverviewAndSecurityDualReviewTest extends TestCase
{
    private array $originalConfig = [];
    private array $tempFiles = [];
    private string $defaultTenantDb = '';
    private AdminUser $superAdmin;
    private AdminUser $limitedAdmin;
    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConfig = [
            'tenancy.bootstrappers' => config('tenancy.bootstrappers'),
            'database.connections.mysql' => config('database.connections.mysql'),
            'database.connections.tenant' => config('database.connections.tenant'),
            'auth.guards' => config('auth.guards'),
        ];

        $this->defaultTenantDb = tempnam(sys_get_temp_dir(), 'ovr_ten_def_') . '.sqlite';
        touch($this->defaultTenantDb);
        $this->tempFiles[] = $this->defaultTenantDb;
        TestOverviewTenancyBootstrapper::$defaultDb = $this->defaultTenantDb;
        TestOverviewTenancyBootstrapper::$databases = [];

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->defaultTenantDb, 'prefix' => ''],
            'tenancy.bootstrappers' => [TestOverviewTenancyBootstrapper::class],
            'activitylog.enabled' => false,
        ]);

        DB::purge('mysql');
        DB::purge('tenant');

        $pdo = DB::connection('mysql')->getPdo();
        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                if (! $date) return null;
                $timestamp = strtotime((string) $date);
                if (! $timestamp) return null;
                $phpFormat = str_replace(['%Y', '%m', '%d', '%H', '%i', '%s'], ['Y', 'm', 'd', 'H', 'i', 's'], (string) $format);
                return date($phpFormat, $timestamp);
            }, 2);
        }

        $this->setUpSchemas();
        $this->seedInitialData();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        foreach ($this->originalConfig as $key => $value) {
            config([$key => $value]);
        }

        DB::purge('mysql');
        DB::purge('tenant');

        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function setUpSchemas(): void
    {
        $this->setUpCentralSchemas();
        $this->setUpTenantSchemas('tenant');
    }

    private function setUpCentralSchemas(): void
    {
        // Central Schemas
        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->boolean('is_super_admin')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::connection('mysql')->create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::connection('mysql')->create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('company_name')->default('Test Company');
            $table->string('commercial_number')->default('1234567890');
            $table->string('access_type')->default('path');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Default Plan');
            $table->string('limits_mode')->default('unlimited');
            $table->unsignedInteger('max_users')->default(10);
            $table->unsignedInteger('max_properties')->default(100);
            $table->unsignedInteger('max_auctions')->default(50);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        Schema::connection('mysql')->create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        Schema::connection('mysql')->create('contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description')->nullable();
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->string('event')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('subscription_number')->nullable();
            $table->string('tenant_id');
            $table->unsignedBigInteger('plan_id')->default(1);
            $table->string('status')->default('active');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('status')->default('paid');
            $table->decimal('amount', 10, 2)->default(100.00);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('trial_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('auction_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Standard Template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('subscription_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function setUpTenantSchemas(string $connection = 'tenant'): void
    {
        // Tenant Schemas
        Schema::connection($connection)->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('global_uid')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->boolean('is_super_admin')->default(false);
            $table->string('image')->nullable();
            $table->string('background_image')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($connection)->create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::connection($connection)->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::connection($connection)->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::connection($connection)->create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::connection($connection)->create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::connection($connection)->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        Schema::connection($connection)->create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description')->nullable();
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->string('event')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('assignment_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($connection)->create('properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('property_number')->nullable();
            $table->string('name');
            $table->string('type')->default('residential_land');
            $table->string('usage_classification')->default('residential');
            $table->string('data_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($connection)->create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status')->default('active');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection($connection)->create('auction_properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('property_id');
            $table->string('result_status')->nullable();
            $table->timestamps();
        });
    }

    private function seedInitialData(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Central SuperAdmin
        $this->superAdmin = AdminUser::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'password' => 'SuperSecret123!',
            'status' => 'active',
            'is_super_admin' => true,
        ]);
        $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $this->superAdmin->assignRole($superAdminRole);

        $this->limitedAdmin = AdminUser::create([
            'name' => 'Limited Admin',
            'email' => 'limited@example.com',
            'password' => 'LimitedSecret123!',
            'status' => 'active',
            'is_super_admin' => false,
        ]);

        $dashboardMainPerm = Permission::create(['name' => 'dashboard.main', 'guard_name' => 'admin']);
        $adminRole = Role::create(['name' => 'SupportAdmin', 'guard_name' => 'admin']);
        $adminRole->givePermissionTo($dashboardMainPerm);
        $this->limitedAdmin->assignRole($adminRole);

        // Tenants
        $this->tenantA = Tenant::create([
            'id' => 'tenant-a',
            'company_name' => 'Company Alpha',
            'access_type' => 'path',
        ]);

        $this->tenantB = Tenant::create([
            'id' => 'tenant-b',
            'company_name' => 'Company Beta',
            'access_type' => 'path',
        ]);

        Subscription::create([
            'tenant_id' => 'tenant-a',
            'plan_id' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'price' => 1500.00,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addYear(),
        ]);

        // Tenant Users
        $this->tenantUser = User::create([
            'name' => 'Tenant Manager',
            'email' => 'manager@tenant-a.com',
            'password' => 'TenantPassword123!',
            'status' => 'active',
            'is_super_admin' => false,
        ]);

        $dashboardViewPerm = Permission::create(['name' => 'dashboard.view', 'guard_name' => 'tenant']);
        $tenantRole = Role::create(['name' => 'TenantManager', 'guard_name' => 'tenant']);
        $tenantRole->givePermissionTo($dashboardViewPerm);
        $this->tenantUser->assignRole($tenantRole);
    }

    /**
     * OVR-01 & OVR-09: Central Dashboard Overview and Subscriptions Distribution
     */
    public function test_central_dashboard_overview_data_and_exclusive_subscription_distribution(): void
    {
        $this->actingAs($this->superAdmin, 'admin');

        $service = app(AdminDashboardService::class);
        $data = $service->getDashboardData();

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('platformActivity', $data);
        $this->assertArrayHasKey('subscriptionStatus', $data);

        // Subscription distribution has exclusive 5 statuses and NO total slice
        $statuses = $data['subscriptionStatus'];
        $this->assertIsArray($statuses);
        $keys = array_column($statuses, 'key');
        $this->assertNotContains('total', $keys, 'Central subscription status distribution must not include total as a slice.');
        $this->assertEqualsCanonicalizing(['pending', 'active', 'suspended', 'cancelled', 'expired'], $keys);

        // Platform Activity endpoint
        $response = $this->getJson(route('admin.dashboard.platform-activity', ['range' => '3months']));
        $response->assertOk()->assertJsonStructure(['data' => [['label', 'count']]]);
    }

    /**
     * OVR-02: Permission boundary: dashboard.main required in Central, dashboard.view in Tenant
     */
    public function test_dashboard_baseline_permission_enforcement(): void
    {
        // Central: SuperAdmin has access
        $this->actingAs($this->superAdmin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk();

        // Central: Limited admin with dashboard.main has access
        $this->actingAs($this->limitedAdmin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk();

        // Central: User without dashboard.main gets 403
        $unauthorizedAdmin = AdminUser::create([
            'name' => 'No Dashboard Admin',
            'email' => 'no-dashboard@example.com',
            'password' => 'NoDashboard123!',
            'status' => 'active',
            'is_super_admin' => false,
        ]);
        $emptyRole = Role::create(['name' => 'NoOverviewRole', 'guard_name' => 'admin']);
        $unauthorizedAdmin->assignRole($emptyRole);

        $this->actingAs($unauthorizedAdmin, 'admin');
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    /**
     * OVR-05, OVR-06, OVR-07, OVR-08 & U-01:
     * Tenant Property Status Distribution:
     * - Every property counted in EXACTLY ONE slice.
     * - Total is NOT a slice in the chart.
     * - Slices sum to total properties count.
     * - Percentages sum to ~100%.
     * - Empty state handles zero properties gracefully.
     * - Unknown/null status falls into needs_review.
     * - Soft-deleted properties are excluded.
     */
    public function test_tenant_property_status_distribution_exclusivity_and_completeness(): void
    {
        tenancy()->initialize($this->tenantA);

        $service = app(TenantDashboardService::class);

        // 1. Empty state: 0 properties
        $emptyDistribution = $service->getPropertyStatusDistribution();
        $this->assertCount(4, $emptyDistribution);
        $emptyKeys = array_column($emptyDistribution, 'key');
        $this->assertNotContains('total', $emptyKeys);
        $this->assertEquals(0, array_sum(array_column($emptyDistribution, 'count')));
        $this->assertEquals(0, array_sum(array_column($emptyDistribution, 'percentage')));

        // 2. Create an Assignment
        $assignment = Assignment::create(['name' => 'North District Assignment']);

        // 3. Create Properties covering all intersections:
        // P1: Active, Completed, Unlinked -> ready_for_assignment
        $p1 = Property::create([
            'name' => 'Property 1 Ready',
            'data_status' => PropertyDataStatus::COMPLETED->value,
            'is_active' => true,
            'assignment_id' => null,
        ]);

        // P2: Active, Completed, Linked -> linked
        $p2 = Property::create([
            'name' => 'Property 2 Linked',
            'data_status' => PropertyDataStatus::COMPLETED->value,
            'is_active' => true,
            'assignment_id' => $assignment->id,
        ]);

        // P3: Active, Incomplete, Unlinked -> needs_review
        $p3 = Property::create([
            'name' => 'Property 3 Incomplete',
            'data_status' => PropertyDataStatus::INCOMPLETE->value,
            'is_active' => true,
            'assignment_id' => null,
        ]);

        // P4: Active, Null data_status, Unlinked -> needs_review (fallback for unknown/null)
        $p4 = Property::create([
            'name' => 'Property 4 Null Status',
            'data_status' => null,
            'is_active' => true,
            'assignment_id' => null,
        ]);

        // P5: Inactive -> inactive
        $p5 = Property::create([
            'name' => 'Property 5 Inactive',
            'data_status' => PropertyDataStatus::COMPLETED->value,
            'is_active' => false,
            'assignment_id' => null,
        ]);

        // P6: Soft-deleted property -> MUST be excluded from all slices
        $p6 = Property::create([
            'name' => 'Property 6 Deleted',
            'data_status' => PropertyDataStatus::COMPLETED->value,
            'is_active' => true,
            'assignment_id' => null,
        ]);
        $p6->delete();

        $distribution = $service->getPropertyStatusDistribution();
        $this->assertCount(4, $distribution);

        $keyed = collect($distribution)->keyBy('key');

        // Check keys exist and NO total slice
        $this->assertArrayHasKey('ready_for_assignment', $keyed);
        $this->assertArrayHasKey('linked', $keyed);
        $this->assertArrayHasKey('needs_review', $keyed);
        $this->assertArrayHasKey('inactive', $keyed);
        $this->assertFalse($keyed->has('total'), 'Distribution must NOT include total as a slice.');

        // Counts validation: Total active non-deleted = 5
        $this->assertEquals(1, $keyed['ready_for_assignment']['count'], 'P1 must be in ready_for_assignment');
        $this->assertEquals(1, $keyed['linked']['count'], 'P2 must be in linked');
        $this->assertEquals(2, $keyed['needs_review']['count'], 'P3 & P4 must be in needs_review');
        $this->assertEquals(1, $keyed['inactive']['count'], 'P5 must be in inactive');

        // Total sum = 5
        $totalCount = array_sum(array_column($distribution, 'count'));
        $this->assertEquals(5, $totalCount);
        $this->assertEquals(Property::count(), $totalCount);

        // Percentages sum to 100%
        $totalPercentage = array_sum(array_column($distribution, 'percentage'));
        $this->assertEquals(100.0, $totalPercentage);

        // 4. Test state transition (OVR-08): Link P1 to Assignment -> moves to linked
        $p1->update(['assignment_id' => $assignment->id]);
        $updatedDistribution = collect($service->getPropertyStatusDistribution())->keyBy('key');
        $this->assertEquals(0, $updatedDistribution['ready_for_assignment']['count']);
        $this->assertEquals(2, $updatedDistribution['linked']['count']);
        $this->assertEquals(5, array_sum(array_column($updatedDistribution->toArray(), 'count')));

        // 5. Restore P6 from trash -> included again
        $p6->restore();
        $restoredDistribution = collect($service->getPropertyStatusDistribution())->keyBy('key');
        $this->assertEquals(6, array_sum(array_column($restoredDistribution->toArray(), 'count')));
    }

    /**
     * SEC-01, SEC-02, SEC-03: Password Change for Central Admin and Tenant User
     */
    public function test_password_change_enforcement_and_isolation(): void
    {
        // Central Admin Password Change
        $this->actingAs($this->superAdmin, 'admin');

        // 1. Rejects wrong current password
        $this->post(route('admin.users.password.update'), [
            'current_password' => 'WrongPassword123!',
            'new_password' => 'FreshStrongPassword123!',
            'new_password_confirmation' => 'FreshStrongPassword123!',
        ])->assertSessionHasErrors('current_password');

        // 2. Rejects mismatched confirmation
        $this->post(route('admin.users.password.update'), [
            'current_password' => 'SuperSecret123!',
            'new_password' => 'FreshStrongPassword123!',
            'new_password_confirmation' => 'DifferentPassword123!',
        ])->assertSessionHasErrors('new_password');

        // 3. Successfully changes password and hashes it
        $this->post(route('admin.users.password.update'), [
            'current_password' => 'SuperSecret123!',
            'new_password' => 'BrandNewPassword123!',
            'new_password_confirmation' => 'BrandNewPassword123!',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->superAdmin->fresh()->password));

        // 4. Self-protection: cannot alter other admin's password via self-service endpoint
        $this->post(route('admin.users.password.update'), [
            'user_id' => $this->limitedAdmin->id,
            'current_password' => 'BrandNewPassword123!',
            'new_password' => 'TamperedPassword123!',
            'new_password_confirmation' => 'TamperedPassword123!',
        ])->assertSessionHas('success');

        // Limited admin's password remained intact
        $this->assertTrue(Hash::check('LimitedSecret123!', $this->limitedAdmin->fresh()->password));
    }

    /**
     * SEC-04, SEC-05, SEC-06 & U-03: 2FA Recovery Codes Copyable Structure
     */
    public function test_2fa_recovery_codes_rendering_contract_and_copyable_code_wrapper(): void
    {
        // Central 2FA settings view contains copyable-code-wrapper and copy icon logic
        $this->actingAs($this->superAdmin, 'admin');
        $adminView = view('admin.auth.two-factor-settings', [
            'enabled' => true,
            'enforced' => false,
        ])->render();

        $this->assertStringContainsString('copyable-code-wrapper', $adminView);
        $this->assertStringContainsString('copyCodeToClipboard', $adminView);
        $this->assertStringContainsString('ti-copy', $adminView);
        $this->assertStringContainsString('data-code', $adminView);

        // Tenant 2FA settings view contains copyable-code-wrapper and copy icon logic
        tenancy()->initialize($this->tenantA);
        $this->actingAs($this->tenantUser, 'tenant');
        $tenantView = view('tenant.auth.two-factor-settings', [
            'enabled' => true,
            'enforced' => false,
        ])->render();

        $this->assertStringContainsString('copyable-code-wrapper', $tenantView);
        $this->assertStringContainsString('copyCodeToClipboard', $tenantView);
        $this->assertStringContainsString('ti-copy', $tenantView);
        $this->assertStringContainsString('data-code', $tenantView);
    }

    /**
     * OVR-04, OVR-09: Tenant KPI counters and Auction Activity across multiple time ranges
     */
    public function test_tenant_dashboard_kpis_and_auction_activity_ranges(): void
    {
        tenancy()->initialize($this->tenantA);
        $this->actingAs($this->tenantUser, 'tenant');

        $service = app(TenantDashboardService::class);
        $data = $service->getDashboardData();

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('auctionActivity', $data);
        $this->assertArrayHasKey('propertyStatus', $data);

        // Test all valid range parameters
        foreach (['1month', '3months', '6months', '1year'] as $range) {
            $activity = $service->getAuctionActivityByRange($range);
            $this->assertIsArray($activity);
            $this->assertNotEmpty($activity);
            $this->assertArrayHasKey('label', $activity[0]);
            $this->assertArrayHasKey('count', $activity[0]);
        }
    }

    /**
     * OVR-11: Multi-tenant dual DB isolation
     */
    public function test_dual_tenant_dashboard_isolation(): void
    {
        // Set up separate SQLite DB file for Tenant B
        $tenantBDb = tempnam(sys_get_temp_dir(), 'ovr_ten_b_') . '.sqlite';
        touch($tenantBDb);
        $this->tempFiles[] = $tenantBDb;
        TestOverviewTenancyBootstrapper::$databases['tenant-b'] = $tenantBDb;

        // Initialize Tenant B and create schema on it
        tenancy()->initialize($this->tenantB);
        $this->setUpTenantSchemas('tenant');

        Property::create([
            'name' => 'Tenant B Exclusive Property',
            'data_status' => PropertyDataStatus::COMPLETED->value,
            'is_active' => true,
        ]);

        $serviceB = app(TenantDashboardService::class);
        $distributionB = $serviceB->getPropertyStatusDistribution();
        $totalB = array_sum(array_column($distributionB, 'count'));
        $this->assertEquals(1, $totalB, 'Tenant B must see exactly 1 property.');

        // Switch to Tenant A
        tenancy()->initialize($this->tenantA);
        $serviceA = app(TenantDashboardService::class);
        $distributionA = $serviceA->getPropertyStatusDistribution();
        $totalA = array_sum(array_column($distributionA, 'count'));
        $this->assertEquals(0, $totalA, 'Tenant A must NOT see Tenant B properties.');
    }
}


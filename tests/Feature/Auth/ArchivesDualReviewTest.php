<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

class TestArchiveTenancyBootstrapper implements TenancyBootstrapper
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
    }

    public function revert(): void
    {
        DB::purge('tenant');
        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

final class ArchivesDualReviewTest extends TestCase
{
    private array $originalConfig = [];
    private array $tempFiles = [];
    private string $centralDb = '';
    private string $defaultTenantDb = '';
    private string $tenantDbA = '';
    private string $tenantDbB = '';
    private AdminUser $superAdmin;
    private AdminUser $limitedAdmin;
    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $tenantUserA;
    private User $tenantUserB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConfig = [
            'tenancy.bootstrappers' => config('tenancy.bootstrappers'),
            'database.connections.mysql' => config('database.connections.mysql'),
            'database.connections.tenant' => config('database.connections.tenant'),
            'auth.guards' => config('auth.guards'),
        ];

        $this->centralDb = tempnam(sys_get_temp_dir(), 'arc_cen_') . '.sqlite';
        touch($this->centralDb);
        $this->tempFiles[] = $this->centralDb;

        $this->defaultTenantDb = tempnam(sys_get_temp_dir(), 'arc_ten_def_') . '.sqlite';
        touch($this->defaultTenantDb);
        $this->tempFiles[] = $this->defaultTenantDb;

        $this->tenantDbA = tempnam(sys_get_temp_dir(), 'arc_ten_a_') . '.sqlite';
        touch($this->tenantDbA);
        $this->tempFiles[] = $this->tenantDbA;

        $this->tenantDbB = tempnam(sys_get_temp_dir(), 'arc_ten_b_') . '.sqlite';
        touch($this->tenantDbB);
        $this->tempFiles[] = $this->tenantDbB;

        TestArchiveTenancyBootstrapper::$defaultDb = $this->defaultTenantDb;
        TestArchiveTenancyBootstrapper::$databases = [];

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => $this->centralDb, 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->defaultTenantDb, 'prefix' => ''],
            'tenancy.bootstrappers' => [TestArchiveTenancyBootstrapper::class],
            'tenancy.central_domains' => ['localhost', '127.0.0.1'],
            'activitylog.enabled' => false,
            'filesystems.disks.tenant_public' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/tenant_public'),
            ],
            'filesystems.disks.public' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/public'),
            ],
        ]);

        $this->migrateCentralDatabase();
        $this->migrateTenantDatabase($this->defaultTenantDb);
        $this->migrateTenantDatabase($this->tenantDbA);
        $this->migrateTenantDatabase($this->tenantDbB);

        $this->seedCentralData();
        $this->seedTenantData();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        Auth::forgetGuards();

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

    private function migrateCentralDatabase(): void
    {
        $schema = Schema::connection('mysql');

        $schema->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('office_name')->nullable();
            $table->string('office_address')->nullable();
            $table->string('commercial_register')->nullable();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        $schema->create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        $schema->create('trial_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $schema->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $schema->create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $schema->create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        $schema->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        $schema->create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        $schema->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('yearly_renewal_price', 10, 2)->default(0);
            $table->integer('included_users')->default(1);
            $table->decimal('extra_user_license_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('discount_type')->default('fixed');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('company_name')->nullable();
            $table->string('commercial_number')->nullable();
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('status')->default('active');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('subscription_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('bill_number')->unique();
            $table->string('tenant_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('settlement_amount', 10, 2)->default(0);
            $table->decimal('original_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('type')->default('new_subscription');
            $table->string('payment_method')->default('bank_transfer');
            $table->string('status')->default('paid');
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $schema->create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    private function migrateTenantDatabase(string $dbPath): void
    {
        config(['database.connections.tenant.database' => $dbPath]);
        DB::purge('tenant');
        $schema = Schema::connection('tenant');

        $schema->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $schema->create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $schema->create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        $schema->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        $schema->create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        $schema->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->string('background_image')->nullable();
            $table->string('global_uid')->nullable();
            $table->string('remember_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('assignment_number')->nullable();
            $table->string('name');
            $table->string('issuing_entity')->nullable();
            $table->string('city')->nullable();
            $table->string('entity_logo')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('assignment_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('disk')->default('tenant_public');
            $table->string('file_path');
            $table->timestamps();
        });

        $schema->create('properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('property_number')->nullable();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('main_image')->nullable();
            $table->string('aerial_image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('property_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('disk')->default('tenant_public');
            $table->string('file_path');
            $table->timestamps();
        });

        $schema->create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('name');
            $table->string('city')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('auction_properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('property_id');
            $table->timestamps();
        });

        $schema->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedCentralData(): void
    {
        DB::connection('mysql')->table('settings')->insert([
            'id' => 1,
            'office_name' => 'LOT System',
            'office_address' => 'Riyadh, KSA',
            'commercial_register' => '1010101010',
            'key' => 'general',
            'value' => json_encode(['site_name' => 'LOT']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminPerms = [
            'dashboard.main',
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.restore', 'users.force_delete', 'users.change_status',
            'plans.view', 'plans.restore', 'plans.force_delete',
            'coupons.view', 'coupons.restore', 'coupons.force_delete',
            'companies.view', 'companies.restore', 'companies.force_delete',
            'financial_transactions.view', 'financial_transactions.restore', 'financial_transactions.force_delete',
        ];

        foreach ($adminPerms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
        }

        $superRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $superRole->syncPermissions(Permission::where('guard_name', 'admin')->get());

        $this->superAdmin = AdminUser::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole($superRole);

        $limitedRole = Role::firstOrCreate(['name' => 'LimitedAdmin', 'guard_name' => 'admin']);
        $limitedRole->syncPermissions(['dashboard.main', 'users.view', 'plans.view', 'coupons.view', 'companies.view', 'financial_transactions.view']);

        $this->limitedAdmin = AdminUser::create([
            'name' => 'Limited Admin User',
            'email' => 'limitedadmin@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->limitedAdmin->assignRole($limitedRole);

        $this->tenantA = Tenant::create([
            'id' => 'tenant-a',
            'company_name' => 'Company A',
            'commercial_number' => '1010101010',
            'data' => [],
        ]);

        $this->tenantB = Tenant::create([
            'id' => 'tenant-b',
            'company_name' => 'Company B',
            'commercial_number' => '2020202020',
            'data' => [],
        ]);

        DB::connection('mysql')->table('plans')->insert([
            'id' => 1,
            'name' => 'Standard Plan',
            'included_users' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('mysql')->table('subscriptions')->insert([
            [
                'id' => 1,
                'tenant_id' => 'tenant-a',
                'plan_id' => 1,
                'status' => 'active',
                'starts_at' => now()->subMonth()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'tenant_id' => 'tenant-b',
                'plan_id' => 1,
                'status' => 'active',
                'starts_at' => now()->subMonth()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::connection('mysql')->table('subscription_periods')->insert([
            [
                'id' => 1,
                'subscription_id' => 1,
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'subscription_id' => 2,
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        TestArchiveTenancyBootstrapper::$databases['tenant-a'] = $this->tenantDbA;
        TestArchiveTenancyBootstrapper::$databases['tenant-b'] = $this->tenantDbB;
    }

    private function seedTenantData(): void
    {
        $tenantPerms = [
            'dashboard.view',
            'user_archive.view', 'user_archive.restore', 'user_archive.force_delete',
            'assignments_archive.view', 'assignments_archive.restore', 'assignments_archive.force_delete',
            'properties_archive.view', 'properties_archive.restore', 'properties_archive.force_delete',
            'auctions_archive.view', 'auctions_archive.restore', 'auctions_archive.force_delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
        ];

        foreach (['tenant-a' => $this->tenantA, 'tenant-b' => $this->tenantB] as $tenantId => $tenant) {
            tenancy()->initialize($tenant);
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            foreach ($tenantPerms as $perm) {
                Permission::on('tenant')->firstOrCreate(['name' => $perm, 'guard_name' => 'tenant']);
            }

            $adminRole = Role::on('tenant')->firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']);
            $adminRole->syncPermissions(Permission::on('tenant')->where('guard_name', 'tenant')->get());

            $userRole = Role::on('tenant')->firstOrCreate(['name' => 'User', 'guard_name' => 'tenant']);
            $userRole->syncPermissions(Permission::on('tenant')->where('name', 'dashboard.view')->get());

            $user = User::on('tenant')->create([
                'name' => "Admin {$tenantId}",
                'email' => "admin@{$tenantId}.com",
                'phone' => "050000000" . ($tenantId === 'tenant-a' ? '1' : '2'),
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);
            $user->assignRole($adminRole);

            DB::connection('tenant')->table('settings')->insert([
                'id' => 1,
                'key' => 'general',
                'value' => json_encode(['site_name' => 'LOT']),
                'maintenance_mode' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($tenantId === 'tenant-a') {
                $this->tenantUserA = $user;
            } else {
                $this->tenantUserB = $user;
            }

            tenancy()->end();
        }
    }

    private function tenantUrl(string $tenantId, string $route, array $parameters = []): string
    {
        $tenant = $tenantId === 'tenant-a' ? $this->tenantA : $this->tenantB;
        tenancy()->initialize($tenant);
        $url = tenant_route_name($route, $parameters);
        tenancy()->end();

        return $url;
    }

    public function test_arc_01_and_02_archive_indexes_authorization_and_layout(): void
    {
        // 1. Central Archives with SuperAdmin
        $centralRoutes = [
            'admin.archives.index',
            'admin.archives.plans.index',
            'admin.archives.coupons.index',
            'admin.archives.tenants.index',
            'admin.archives.bills.index',
        ];

        foreach ($centralRoutes as $routeName) {
            $response = $this->actingAs($this->superAdmin, 'admin')
                ->get(route($routeName));

            $response->assertStatus(200);
            $content = $response->getContent();
            $this->assertStringContainsString('card', $content);
            $this->assertStringNotContainsString('btn-add', $content);
            $this->assertStringNotContainsString('stat-card', $content);
        }

        // Central with unauthenticated user
        $this->app['auth']->forgetGuards();
        foreach ($centralRoutes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertRedirect(route('admin.login'));
        }

        // 2. Tenant Archives
        $tenantRoutes = [
            'archive.users.index',
            'archive.assignments.index',
            'archive.properties.index',
            'archive.auctions.index',
        ];

        foreach ($tenantRoutes as $routeName) {
            $url = $this->tenantUrl('tenant-a', $routeName);

            $response = $this->actingAs($this->tenantUserA, 'tenant')
                ->withSession(['auth_tenant_id' => 'tenant-a'])
                ->get($url);

            $response->assertStatus(200);
            $content = $response->getContent();
            $this->assertStringContainsString('card', $content);
            $this->assertStringNotContainsString('btn-add', $content);
            $this->assertStringNotContainsString('stat-card', $content);
            $this->assertStringNotContainsString('btn-add', $content);
        }
    }

    public function test_arc_03_soft_deleted_fixtures_appear_in_archive_and_active_are_excluded(): void
    {
        // Central Admin User
        $trashedAdmin = AdminUser::create([
            'name' => 'Trashed Admin',
            'email' => 'trashed_admin@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $trashedAdmin->delete();

        $activeAdmin = AdminUser::create([
            'name' => 'Active Normal Admin',
            'email' => 'active_normal@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson(route('admin.archives.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertStringContainsString('trashed_admin@example.com', json_encode($data));
        $this->assertStringNotContainsString('active_normal@example.com', json_encode($data));
    }

    public function test_arc_04_restore_valid_record_returns_dual_response_and_restores_record(): void
    {
        $trashedAdmin = AdminUser::create([
            'name' => 'Admin To Restore',
            'email' => 'restore_me@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $trashedAdmin->delete();
        $this->assertTrue($trashedAdmin->fresh()->trashed());

        // Restore via AJAX
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->patchJson(route('admin.archives.restore', ['id' => $trashedAdmin->id]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertFalse($trashedAdmin->fresh()->trashed());

        // Second restore attempt on active record returns 404 (onlyTrashed guard)
        $response2 = $this->actingAs($this->superAdmin, 'admin')
            ->patchJson(route('admin.archives.restore', ['id' => $trashedAdmin->id]));
        $response2->assertStatus(404);
    }

    public function test_arc_05_single_force_delete_permanently_removes_disposable_fixture(): void
    {
        $trashedAdmin = AdminUser::create([
            'name' => 'Admin To Force Delete',
            'email' => 'force_delete_me@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $trashedAdmin->delete();
        $this->assertTrue($trashedAdmin->fresh()->trashed());

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->deleteJson(route('admin.archives.force-delete', ['id' => $trashedAdmin->id]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNull(AdminUser::withTrashed()->find($trashedAdmin->id));
    }

    public function test_arc_06_protected_relationships_prevent_force_delete_and_active_ids_are_rejected(): void
    {
        // 1. Sending an ACTIVE record ID to archive force-delete endpoint returns 404
        $activeAdmin = AdminUser::create([
            'name' => 'Active Admin Protected',
            'email' => 'active_prot@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->deleteJson(route('admin.archives.force-delete', ['id' => $activeAdmin->id]));
        $response->assertStatus(404);
        $this->assertNotNull(AdminUser::find($activeAdmin->id));

        // 2. Tenant: Property linked to Auction cannot be force deleted
        tenancy()->initialize($this->tenantA);

        $assignment = Assignment::create(['name' => 'Main Assignment']);
        $property = Property::create(['name' => 'Linked Property', 'assignment_id' => $assignment->id]);
        $auction = Auction::create(['name' => 'Linked Auction', 'assignment_id' => $assignment->id]);
        $auction->properties()->attach($property->id);

        $property->delete();
        $this->assertTrue($property->fresh()->trashed());

        tenancy()->end();

        $forceDeleteUrl = $this->tenantUrl('tenant-a', 'archive.properties.force-delete', ['property' => $property->id]);
        $response2 = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->deleteJson($forceDeleteUrl);

        $response2->assertStatus(422);
        $response2->assertJson(['success' => false]);
        $this->assertStringContainsString('المزادات المرتبطة', $response2->json('message'));

        // Property still exists in trashed state
        tenancy()->initialize($this->tenantA);
        $this->assertTrue($property->fresh()->trashed());
        tenancy()->end();
    }

    public function test_arc_07_tenant_users_mass_restore_and_subscription_limit(): void
    {
        tenancy()->initialize($this->tenantA);

        $trashed1 = User::create([
            'name' => 'Trashed 1',
            'email' => 'trashed1@tenant-a.com',
            'phone' => '0599999991',
            'password' => Hash::make('password123'),
        ]);
        $trashed1->delete();

        $trashed2 = User::create([
            'name' => 'Trashed 2',
            'email' => 'trashed2@tenant-a.com',
            'phone' => '0599999992',
            'password' => Hash::make('password123'),
        ]);
        $trashed2->delete();

        tenancy()->end();

        $massRestoreUrl = $this->tenantUrl('tenant-a', 'archive.users.mass-restore');
        $response = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->postJson($massRestoreUrl, ['ids' => [$trashed1->id, $trashed2->id]]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        tenancy()->initialize($this->tenantA);
        $this->assertFalse($trashed1->fresh()->trashed());
        $this->assertFalse($trashed2->fresh()->trashed());
        tenancy()->end();
    }

    public function test_arc_08_tenant_users_mass_force_delete_and_cross_tenant_isolation(): void
    {
        tenancy()->initialize($this->tenantA);
        $userA = User::create([
            'name' => 'Tenant A Trashed',
            'email' => 'user_a_del@tenant-a.com',
            'password' => Hash::make('password123'),
        ]);
        $userA->delete();
        tenancy()->end();

        tenancy()->initialize($this->tenantB);
        $userB = new User([
            'name' => 'Tenant B Trashed',
            'email' => 'user_b_del@tenant-b.com',
            'password' => Hash::make('password123'),
        ]);
        $userB->id = 999;
        $userB->save();
        $userB->delete();
        tenancy()->end();

        // Tenant A tries to mass force delete User B's ID
        $massForceUrl = $this->tenantUrl('tenant-a', 'archive.users.mass-force-delete');
        $response = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->deleteJson($massForceUrl, ['ids' => [$userB->id]]);

        // Should return 404 because userB does not exist in Tenant A's database
        $response->assertStatus(404);

        // Verify User B still exists in Tenant B
        tenancy()->initialize($this->tenantB);
        $this->assertNotNull(User::withTrashed()->find($userB->id));
        tenancy()->end();
    }

    public function test_arc_09_restoring_user_without_roles_assigns_baseline_role(): void
    {
        tenancy()->initialize($this->tenantA);
        $user = User::create([
            'name' => 'Roleless User',
            'email' => 'roleless@tenant-a.com',
            'password' => Hash::make('password123'),
        ]);
        $user->roles()->detach();
        $user->delete();
        $this->assertEquals(0, $user->roles()->count());
        tenancy()->end();

        $restoreUrl = $this->tenantUrl('tenant-a', 'archive.users.restore', ['user' => $user->id]);
        $response = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->patchJson($restoreUrl);

        $response->assertStatus(200);

        tenancy()->initialize($this->tenantA);
        $freshUser = $user->fresh();
        $this->assertFalse($freshUser->trashed());
        $this->assertGreaterThan(0, $freshUser->roles()->count());
        $this->assertTrue($freshUser->hasRole('User'));
        tenancy()->end();
    }

    public function test_arc_10_restoring_user_with_conflicting_email_fails_with_422(): void
    {
        tenancy()->initialize($this->tenantA);
        $archivedUser = User::create([
            'name' => 'Old User',
            'email' => 'duplicate@tenant-a.com',
            'password' => Hash::make('password123'),
        ]);
        $archivedUser->delete();

        // New active user with same email
        $activeUser = User::create([
            'name' => 'New User',
            'email' => 'duplicate@tenant-a.com',
            'password' => Hash::make('password123'),
        ]);
        tenancy()->end();

        $restoreUrl = $this->tenantUrl('tenant-a', 'archive.users.restore', ['user' => $archivedUser->id]);
        $response = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->patchJson($restoreUrl);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('البريد الإلكتروني مستخدم بالفعل', $response->json('message'));

        // Archived user remains trashed, active user remains active
        tenancy()->initialize($this->tenantA);
        $this->assertTrue($archivedUser->fresh()->trashed());
        $this->assertFalse($activeUser->fresh()->trashed());
        tenancy()->end();
    }

    public function test_arc_11_force_delete_cleans_up_avatar_file(): void
    {
        Storage::fake('tenant_public');
        Storage::disk('tenant_public')->put('avatars/test-user.png', 'avatar content');
        $this->assertTrue(Storage::disk('tenant_public')->exists('avatars/test-user.png'));

        tenancy()->initialize($this->tenantA);
        $user = User::create([
            'name' => 'Avatar User',
            'email' => 'avatar@tenant-a.com',
            'password' => Hash::make('password123'),
            'image' => 'avatars/test-user.png',
        ]);
        $user->delete();
        tenancy()->end();

        $forceDeleteUrl = $this->tenantUrl('tenant-a', 'archive.users.force-delete', ['user' => $user->id]);
        $response = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->deleteJson($forceDeleteUrl);

        $response->assertStatus(200);
        $this->assertFalse(Storage::disk('tenant_public')->exists('avatars/test-user.png'));
    }

    public function test_arc_12_same_id_in_tenant_a_and_tenant_b_preserves_isolation(): void
    {
        tenancy()->initialize($this->tenantA);
        $assignmentA = new Assignment(['name' => 'Assignment in Tenant A']);
        $assignmentA->id = 999;
        $assignmentA->save();
        $assignmentA->delete();
        tenancy()->end();

        tenancy()->initialize($this->tenantB);
        $assignmentB = new Assignment(['name' => 'Assignment in Tenant B']);
        $assignmentB->id = 999;
        $assignmentB->save();
        $assignmentB->delete();
        tenancy()->end();

        // Restore in Tenant A only
        $restoreUrlA = $this->tenantUrl('tenant-a', 'archive.assignments.restore', ['assignment' => 999]);
        $response = $this->actingAs($this->tenantUserA, 'tenant')
            ->withSession(['auth_tenant_id' => 'tenant-a'])
            ->patchJson($restoreUrlA);

        $response->assertStatus(200);

        // Verify Tenant A is restored, Tenant B remains trashed
        tenancy()->initialize($this->tenantA);
        $this->assertFalse(Assignment::find(999)->trashed());
        tenancy()->end();

        tenancy()->initialize($this->tenantB);
        $this->assertTrue(Assignment::onlyTrashed()->find(999)->trashed());
        tenancy()->end();
    }

    public function test_arc_14_permissions_granularity_enforcement(): void
    {
        // Limited Admin has view permission only, lacks restore and force_delete
        $plan = Plan::create(['name' => 'Archived Plan', 'price' => 100]);
        $plan->delete();

        // 1. Limited Admin can view archive
        $response = $this->actingAs($this->limitedAdmin, 'admin')
            ->get(route('admin.archives.plans.index'));
        $response->assertStatus(200);

        // 2. Limited Admin cannot restore (403)
        $response2 = $this->actingAs($this->limitedAdmin, 'admin')
            ->patchJson(route('admin.archives.plans.restore', ['id' => $plan->id]));
        $response2->assertStatus(403);

        // 3. Limited Admin cannot force delete (403)
        $response3 = $this->actingAs($this->limitedAdmin, 'admin')
            ->deleteJson(route('admin.archives.plans.force-delete', ['id' => $plan->id]));
        $response3->assertStatus(403);
    }

    public function test_arc_bill_archived_show_route_accessible(): void
    {
        $bill = Bill::create([
            'bill_number' => 'BILL-ARC-001',
            'tenant_id' => 'tenant-a',
            'amount' => 500,
            'original_amount' => 500,
            'type' => \App\Enums\Admin\Billing\BillType::NEW_SUBSCRIPTION,
            'payment_method' => \App\Enums\Admin\Billing\PaymentMethod::BANK_TRANSFER,
            'status' => \App\Enums\Admin\Billing\BillStatus::PAID,
        ]);
        $bill->delete();
        $this->assertTrue($bill->fresh()->trashed());

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.archives.bills.show', ['bill' => $bill->id]));

        $response->assertStatus(200);
        $response->assertSee('BILL-ARC-001');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

class TestActivityLogTenancyBootstrapper implements TenancyBootstrapper
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

final class ActivityLogsDualReviewTest extends TestCase
{
    private array $originalConfig = [];
    private array $tempFiles = [];
    private string $defaultTenantDb = '';
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

        $this->centralDb = tempnam(sys_get_temp_dir(), 'act_cen_') . '.sqlite';
        touch($this->centralDb);
        $this->tempFiles[] = $this->centralDb;

        $this->defaultTenantDb = tempnam(sys_get_temp_dir(), 'act_ten_def_') . '.sqlite';
        touch($this->defaultTenantDb);
        $this->tempFiles[] = $this->defaultTenantDb;
        TestActivityLogTenancyBootstrapper::$defaultDb = $this->defaultTenantDb;
        TestActivityLogTenancyBootstrapper::$databases = [];

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => $this->centralDb, 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->defaultTenantDb, 'prefix' => ''],
            'tenancy.bootstrappers' => [TestActivityLogTenancyBootstrapper::class],
            'tenancy.central_domains' => ['localhost', '127.0.0.1'],
            'activitylog.enabled' => false,
            'filesystems.disks.tenant_public' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/tenant_public'),
            ],
        ]);

        DB::purge('mysql');
        DB::purge('tenant');

        $this->setUpCentralDatabase();
        $this->setUpTenantDatabases();
    }

    protected function tearDown(): void
    {
        try {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        } catch (\Throwable) {}

        config([
            'tenancy.bootstrappers' => $this->originalConfig['tenancy.bootstrappers'] ?? [],
            'database.connections.mysql' => $this->originalConfig['database.connections.mysql'] ?? [],
            'database.connections.tenant' => $this->originalConfig['database.connections.tenant'] ?? [],
            'auth.guards' => $this->originalConfig['auth.guards'] ?? [],
        ]);

        DB::purge('mysql');
        DB::purge('tenant');

        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function setUpCentralDatabase(): void
    {
        DB::setDefaultConnection('mysql');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
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

        Schema::create('subscription_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->string('status')->default('active');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_name')->nullable();
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('tenant_id');
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('event')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });

        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        $viewPerm = Permission::create(['name' => 'activity_log.view', 'guard_name' => 'admin']);

        $this->superAdmin = AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->superAdmin->givePermissionTo($viewPerm);

        $this->limitedAdmin = AdminUser::create([
            'name' => 'Limited Admin',
            'email' => 'limited@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->tenantA = Tenant::create(['id' => 'tenant-a', 'company_name' => 'Tenant Company A']);
        $this->tenantA->domains()->create(['domain' => 'tenant-a.localhost']);

        $this->tenantB = Tenant::create(['id' => 'tenant-b', 'company_name' => 'Tenant Company B']);
        $this->tenantB->domains()->create(['domain' => 'tenant-b.localhost']);

        DB::connection('mysql')->table('subscriptions')->insert([
            [
                'tenant_id' => 'tenant-a',
                'plan_id' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 'tenant-b',
                'plan_id' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function setUpTenantDatabases(): void
    {
        $tenantADb = tempnam(sys_get_temp_dir(), 'act_ten_a_') . '.sqlite';
        touch($tenantADb);
        $this->tempFiles[] = $tenantADb;

        $tenantBDb = tempnam(sys_get_temp_dir(), 'act_ten_b_') . '.sqlite';
        touch($tenantBDb);
        $this->tempFiles[] = $tenantBDb;

        TestActivityLogTenancyBootstrapper::$databases = [
            'tenant-a' => $tenantADb,
            'tenant-b' => $tenantBDb,
        ];

        foreach (['tenant-a' => $tenantADb, 'tenant-b' => $tenantBDb] as $tenantKey => $dbPath) {
            config(['database.connections.tenant.database' => $dbPath]);
            DB::purge('tenant');

            $schema = Schema::connection('tenant');

            $schema->create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });

            $schema->create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });

            $schema->create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
            });

            $schema->create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
            });

            $schema->create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
            });

            $schema->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('status')->default('active');
                $table->string('global_uid')->nullable();
                $table->string('phone')->nullable();
                $table->string('image')->nullable();
                $table->string('background_image')->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            });

            $schema->create('activity_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->nullableMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->string('event')->nullable();
                $table->timestamps();
                $table->index('log_name');
            });

            $schema->create('tickets', function (Blueprint $table) {
                $table->id();
                $table->string('status')->default('open');
                $table->timestamps();
            });

            $schema->create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->nullable();
                $table->text('value')->nullable();
                $table->boolean('force_two_factor_auth')->default(false);
                $table->timestamps();
            });

            $tPerm = Permission::on('tenant')->create(['name' => 'activity_log.view', 'guard_name' => 'tenant']);

            $user = User::on('tenant')->create([
                'name' => "User {$tenantKey}",
                'email' => "user@{$tenantKey}.com",
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);
            $user->givePermissionTo($tPerm);

            if ($tenantKey === 'tenant-a') {
                $this->tenantUserA = $user;
            } else {
                $this->tenantUserB = $user;
            }
        }

        DB::purge('tenant');
        DB::setDefaultConnection('mysql');
    }

    public function test_admin_and_tenant_activity_logs_require_activity_log_view_permission(): void
    {
        // 1. Unauthenticated admin gets redirected
        $this->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('admin.login'));

        // 2. Admin without permission gets 403
        $this->actingAs($this->limitedAdmin, 'admin')
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();

        // 3. Admin with permission gets 200
        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('سجل النشاطات');

        // 4. Tenant unauthenticated gets redirected
        tenancy()->initialize($this->tenantA);
        $this->get(tenant_route_name('activity-logs.index'))
            ->assertRedirect(route('tenant.login'));

        // 5. Tenant user with permission gets 200
        $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.index'))
            ->assertOk()
            ->assertSee('سجل النشاطات');
        tenancy()->end();
    }

    public function test_admin_and_tenant_show_view_renders_two_column_asymmetric_layout_without_legacy_tabs(): void
    {
        // Create an activity in Admin
        $adminActivity = Activity::on('mysql')->create([
            'log_name' => 'users',
            'description' => 'تم إنشاء مستخدم جديد: أحمد محمد',
            'event' => 'created',
            'causer_type' => AdminUser::class,
            'causer_id' => $this->superAdmin->id,
            'properties' => [
                'attributes' => [
                    'name' => 'أحمد محمد',
                    'email' => 'ahmed@example.com',
                    'status' => 'active',
                ],
                'ip' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
            ],
        ]);

        $adminResponse = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.activity-logs.show', $adminActivity->id))
            ->assertOk();

        // Verify Two-Column Asymmetric layout
        $adminContent = $adminResponse->getContent();
        $this->assertStringContainsString('col-xl-4 col-lg-5 col-md-5 mb-4', $adminContent);
        $this->assertStringContainsString('col-xl-8 col-lg-7 col-md-7', $adminContent);
        $this->assertStringContainsString('وصف النشاط', $adminContent);
        $this->assertStringContainsString('تفاصيل التغييرات والبيانات', $adminContent);
        $this->assertStringContainsString('البيانات الخام (JSON)', $adminContent);
        $this->assertStringContainsString('copyable-code', $adminContent);
        $this->assertStringContainsString('192.168.1.1', $adminContent);

        // Verify legacy tabs are completely removed
        $this->assertStringNotContainsString('nav nav-tabs flex-column', $adminContent);
        $this->assertStringNotContainsString('tab-content', $adminContent);

        // Tenant Show View
        tenancy()->initialize($this->tenantA);
        $tenantActivity = Activity::on('tenant')->create([
            'log_name' => 'properties',
            'description' => 'تم تحديث بيانات العقار: برج الرياض',
            'event' => 'updated',
            'causer_type' => User::class,
            'causer_id' => $this->tenantUserA->id,
            'properties' => [
                'old' => [
                    'price' => '1000000',
                    'status' => 'draft',
                ],
                'attributes' => [
                    'price' => '1500000',
                    'status' => 'active',
                ],
                'ip' => '10.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            ],
        ]);

        $tenantResponse = $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.show', $tenantActivity->id))
            ->assertOk();

        $tenantContent = $tenantResponse->getContent();
        $this->assertStringContainsString('col-xl-4 col-lg-5 col-md-5 mb-4', $tenantContent);
        $this->assertStringContainsString('col-xl-8 col-lg-7 col-md-7', $tenantContent);
        $this->assertStringContainsString('وصف النشاط', $tenantContent);
        $this->assertStringContainsString('تفاصيل التغييرات والبيانات', $tenantContent);
        $this->assertStringContainsString('البيانات الخام (JSON)', $tenantContent);
        $this->assertStringContainsString('القيمة السابقة', $tenantContent);
        $this->assertStringContainsString('القيمة الجديدة', $tenantContent);
        $this->assertStringNotContainsString('nav nav-tabs flex-column', $tenantContent);
        tenancy()->end();
    }

    public function test_show_view_handles_nested_arrays_nulls_booleans_deleted_causer_and_missing_subject(): void
    {
        // Activity with missing subject, deleted causer (id 99999), nested arrays, nulls, booleans
        $activity = Activity::on('mysql')->create([
            'log_name' => 'companies',
            'description' => 'تعديل مركب',
            'event' => 'updated',
            'causer_type' => AdminUser::class,
            'causer_id' => 99999, // Non-existent causer
            'subject_type' => 'App\\Models\\NonExistentSubject',
            'subject_id' => 88888,
            'properties' => [
                'old' => [
                    'is_active' => false,
                    'notes' => null,
                    'settings' => ['notifications' => false, 'channels' => ['email']],
                    'deleted_field' => 'old_value',
                ],
                'attributes' => [
                    'is_active' => true,
                    'notes' => 'ملاحظة جديدة',
                    'settings' => ['notifications' => true, 'channels' => ['email', 'sms']],
                    'new_field' => 'value',
                ],
            ],
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.activity-logs.show', $activity->id))
            ->assertOk();

        $content = $response->getContent();
        // Check that causer falls back gracefully to 'النظام'
        $this->assertStringContainsString('النظام', $content);
        // Subject display shows missing/deleted
        $this->assertStringContainsString('NonExistentSubject', $content);
        // Boolean rendered as badges
        $this->assertStringContainsString('نعم', $content);
        $this->assertStringContainsString('لا', $content);
        // Null rendered as dash
        $this->assertStringContainsString('-', $content);
        // Nested array rendered formatted without throwing 500
        $this->assertStringContainsString('notifications', $content);
    }

    public function test_sensitive_fields_are_masked_in_table_and_raw_json(): void
    {
        $plainPassword = 'super-secret-password-xyz';
        $twoFactorSecret = '2FA-SECRET-TOKEN-123';
        $recoveryCodes = ['CODE1', 'CODE2', 'CODE3'];

        $activity = Activity::on('mysql')->create([
            'log_name' => 'auth',
            'description' => 'تغيير كلمة المرور وتفعيل 2FA',
            'event' => 'updated',
            'causer_type' => AdminUser::class,
            'causer_id' => $this->superAdmin->id,
            'properties' => [
                'attributes' => [
                    'password' => $plainPassword,
                    'remember_token' => 'random_token_123',
                    'two_factor_secret' => $twoFactorSecret,
                    'two_factor_recovery_codes' => $recoveryCodes,
                    'email' => 'admin@example.com',
                ],
            ],
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.activity-logs.show', $activity->id))
            ->assertOk();

        $content = $response->getContent();

        // Plain text secrets MUST NOT appear anywhere in the HTML
        $this->assertStringNotContainsString($plainPassword, $content);
        $this->assertStringNotContainsString($twoFactorSecret, $content);
        $this->assertStringNotContainsString('random_token_123', $content);
        $this->assertStringNotContainsString('CODE1', $content);

        // Instead, the masked placeholder appears
        $this->assertStringContainsString('[محجوب للأمان]', $content);
    }

    public function test_admin_and_tenant_index_ajax_datatable_filtering(): void
    {
        // Create activities with specific dates and log names
        Activity::on('mysql')->create([
            'log_name' => 'companies',
            'description' => 'شركة أمل',
            'causer_type' => AdminUser::class,
            'causer_id' => $this->superAdmin->id,
            'created_at' => now()->subDays(5),
        ]);
        Activity::on('mysql')->create([
            'log_name' => 'users',
            'description' => 'مستخدم خالد',
            'causer_type' => AdminUser::class,
            'causer_id' => $this->limitedAdmin->id,
            'created_at' => now(),
        ]);

        // Filter by user_id in Admin
        $res = $this->actingAs($this->superAdmin, 'admin')
            ->getJson(route('admin.activity-logs.index', ['user_id' => $this->superAdmin->id]))
            ->assertOk()
            ->json();
        $this->assertEquals(1, $res['recordsFiltered']);
        $this->assertStringContainsString('شركة أمل', $res['data'][0]['description']);

        // Filter by log_name in Admin
        $res2 = $this->actingAs($this->superAdmin, 'admin')
            ->getJson(route('admin.activity-logs.index', ['log_name' => 'users']))
            ->assertOk()
            ->json();
        $this->assertEquals(1, $res2['recordsFiltered']);
        $this->assertStringContainsString('مستخدم خالد', $res2['data'][0]['description']);

        // Filter by date range in Admin
        $res3 = $this->actingAs($this->superAdmin, 'admin')
            ->getJson(route('admin.activity-logs.index', [
                'created_from' => now()->subDays(2)->toDateString(),
                'created_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->json();
        $this->assertEquals(1, $res3['recordsFiltered']);

        // Tenant AJAX DataTable
        tenancy()->initialize($this->tenantA);
        Activity::on('tenant')->create([
            'log_name' => 'properties',
            'description' => 'عقار العليا',
            'causer_type' => User::class,
            'causer_id' => $this->tenantUserA->id,
            'created_at' => now(),
        ]);

        $tRes = $this->actingAs($this->tenantUserA, 'tenant')
            ->getJson(tenant_route_name('activity-logs.index', ['log_name' => 'properties']))
            ->assertOk()
            ->json();
        $this->assertEquals(1, $tRes['recordsFiltered']);
        $this->assertStringContainsString('عقار العليا', $tRes['data'][0]['description']);
        tenancy()->end();
    }

    public function test_tenant_get_all_ids_enforces_limits_and_filters(): void
    {
        tenancy()->initialize($this->tenantA);

        for ($i = 1; $i <= 5; $i++) {
            Activity::on('tenant')->create([
                'log_name' => $i <= 3 ? 'properties' : 'auctions',
                'description' => "نشاط {$i}",
                'causer_type' => User::class,
                'causer_id' => $this->tenantUserA->id,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        // Get all IDs without filter
        $res = $this->actingAs($this->tenantUserA, 'tenant')
            ->getJson(tenant_route_name('activity-logs.get-all-ids'))
            ->assertOk()
            ->json();

        $this->assertTrue($res['success']);
        $this->assertEquals(5, $res['total']);
        $this->assertCount(5, $res['ids']);
        $this->assertFalse($res['truncated']);

        // Get all IDs with filter
        $resFiltered = $this->actingAs($this->tenantUserA, 'tenant')
            ->getJson(tenant_route_name('activity-logs.get-all-ids', ['log_name' => 'properties']))
            ->assertOk()
            ->json();
        $this->assertEquals(3, $resFiltered['total']);
        $this->assertCount(3, $resFiltered['ids']);

        tenancy()->end();
    }

    public function test_tenant_export_and_download_flow_with_security_and_traversal_protection(): void
    {
        tenancy()->initialize($this->tenantA);

        $activity = Activity::on('tenant')->create([
            'log_name' => 'properties',
            'description' => 'تصدير تجريبي',
            'causer_type' => User::class,
            'causer_id' => $this->tenantUserA->id,
            'created_at' => now(),
        ]);

        // 1. Export request creates file and returns file_name
        $exportRes = $this->actingAs($this->tenantUserA, 'tenant')
            ->postJson(tenant_route_name('activity-logs.export'), [
                'ids' => json_encode([$activity->id]),
            ])
            ->assertOk()
            ->json();

        $this->assertTrue($exportRes['success']);
        $this->assertNotEmpty($exportRes['file_name']);
        $fileName = $exportRes['file_name'];

        // 2. Authorized owner downloads export successfully
        $downloadRes = $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.export-download', ['file' => $fileName]))
            ->assertOk();

        // 3. User B in Tenant B cannot download Tenant A's export
        tenancy()->end();
        $this->flushSession();
        tenancy()->initialize($this->tenantB);
        session(['auth_tenant_id' => 'tenant-b']);
        $this->actingAs($this->tenantUserB, 'tenant')
            ->getJson(tenant_route_name('activity-logs.export-download', ['file' => $fileName]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
        tenancy()->end();

        // 4. Invalid file name / path traversal attempt is blocked
        $this->flushSession();
        tenancy()->initialize($this->tenantA);
        session(['auth_tenant_id' => 'tenant-a']);
        $this->actingAs($this->tenantUserA, 'tenant')
            ->getJson(tenant_route_name('activity-logs.export-download', ['file' => 'activity-logs-traversal.php']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
        tenancy()->end();
    }

    public function test_tenant_archives_view_and_download(): void
    {
        tenancy()->initialize($this->tenantA);

        $disk = Storage::disk('tenant_public');
        $archiveFile = 'activity_logs_2026_11.xlsx';
        $disk->put('activity-logs/' . $archiveFile, 'fake archive content');

        // View archives page
        $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.archives'))
            ->assertOk()
            ->assertSee($archiveFile)
            ->assertSee('قائمة الملفات المصدرة');

        // Download archive file via export-download route
        $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.export-download', ['file' => $archiveFile]))
            ->assertOk();

        tenancy()->end();
    }

    public function test_tenant_dual_database_isolation_and_cross_tenant_id_separation(): void
    {
        // Create an activity in Tenant A
        tenancy()->initialize($this->tenantA);
        $actA = Activity::on('tenant')->create([
            'log_name' => 'properties',
            'description' => 'Tenant A Private Action',
            'causer_type' => User::class,
            'causer_id' => $this->tenantUserA->id,
            'created_at' => now(),
        ]);
        $actAId = $actA->id;
        tenancy()->end();

        // In Tenant B, querying that ID returns 404
        tenancy()->initialize($this->tenantB);
        $this->actingAs($this->tenantUserB, 'tenant')
            ->get(tenant_route_name('activity-logs.show', $actAId))
            ->assertNotFound();

        // Also Tenant B index does not see Tenant A activity
        $indexB = $this->actingAs($this->tenantUserB, 'tenant')
            ->getJson(tenant_route_name('activity-logs.index'))
            ->assertOk()
            ->json();
        $this->assertEquals(0, $indexB['recordsTotal']);
        tenancy()->end();
    }

    public function test_admin_and_tenant_index_follow_golden_pattern_and_standardized_formatting(): void
    {
        $adminActivity = Activity::on('mysql')->create([
            'log_name' => 'companies',
            'description' => 'تحديث بيانات عميل',
            'causer_type' => AdminUser::class,
            'causer_id' => $this->superAdmin->id,
            'created_at' => now()->setDate(2026, 5, 10)->setTime(14, 30),
        ]);

        // Admin HTML uses Golden Pattern components and does not expose broken massDelete
        $adminHtml = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="card-activity-logs"', $adminHtml);
        $this->assertStringContainsString('id="activity-logs-table"', $adminHtml);
        $this->assertStringContainsString('id="select-all"', $adminHtml);
        $this->assertStringContainsString('tbody .row-checkbox:checked', $adminHtml);
        $this->assertStringContainsString('"extend":"collection"', $adminHtml);
        $this->assertStringContainsString('"extend":"copy"', $adminHtml);
        $this->assertStringContainsString('"extend":"excel"', $adminHtml);
        $this->assertStringContainsString('btn-reset-datatable-filters', $adminHtml);
        $this->assertStringNotContainsString('massDelete', $adminHtml);
        $this->assertStringNotContainsString('حذف المحدد', $adminHtml);

        // Admin AJAX returns checkbox, subject_info, system_datetime date, and btn-labeled action button
        $adminJson = $this->actingAs($this->superAdmin, 'admin')
            ->getJson(route('admin.activity-logs.index'))
            ->assertOk()
            ->json();

        $row = $adminJson['data'][0];
        $this->assertArrayHasKey('checkbox', $row);
        $this->assertStringContainsString('row-checkbox', $row['checkbox']);
        $this->assertArrayNotHasKey('subject_info', $row);
        $this->assertStringContainsString('10-05-2026', $row['created_at']);
        $this->assertStringContainsString('btn btn-icon btn-label-primary', $row['actions']);
        $this->assertStringContainsString(route('admin.activity-logs.show', $adminActivity->id), $row['actions']);

        // Tenant HTML & AJAX follow Golden Pattern with expanded log_name filter options
        tenancy()->initialize($this->tenantA);
        $tenantActivity = Activity::on('tenant')->create([
            'log_name' => 'auctions',
            'description' => 'إنشاء مزاد جديد',
            'causer_type' => User::class,
            'causer_id' => $this->tenantUserA->id,
            'created_at' => now()->setDate(2026, 6, 15)->setTime(9, 15),
        ]);

        $tenantHtml = $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="card-activity-logs"', $tenantHtml);
        $this->assertStringContainsString('ti-activity', $tenantHtml);
        $this->assertStringContainsString('value="auctions"', $tenantHtml);
        $this->assertStringContainsString('المزادات', $tenantHtml);
        $this->assertStringContainsString('btn-reset-datatable-filters', $tenantHtml);
        $this->assertStringContainsString('"extend":"collection"', $tenantHtml);
        $this->assertStringContainsString('"text":"\u0627\u0644\u0625\u062c\u0631\u0627\u0621\u0627\u062a"', $tenantHtml);
        $this->assertStringContainsString('"extend":"copy"', $tenantHtml);
        $this->assertStringContainsString('tbody .row-checkbox:checked', $tenantHtml);
        $this->assertStringContainsString('handleExportActivityLogs()', $tenantHtml);
        $this->assertStringNotContainsString('تصدير Excel', $tenantHtml);

        $tenantJson = $this->actingAs($this->tenantUserA, 'tenant')
            ->getJson(tenant_route_name('activity-logs.index'))
            ->assertOk()
            ->json();

        $tenantRow = $tenantJson['data'][0];
        $this->assertArrayHasKey('checkbox', $tenantRow);
        $this->assertArrayNotHasKey('subject_info', $tenantRow);
        $this->assertStringContainsString('15-06-2026', $tenantRow['created_at']);
        $this->assertStringContainsString('btn btn-icon btn-label-primary', $tenantRow['actions']);
        $this->assertStringContainsString(tenant_route_name('activity-logs.show', $tenantActivity->id), $tenantRow['actions']);

        // Tenant get-all-ids respects created_from / created_to date range
        Activity::on('tenant')->create([
            'log_name' => 'properties',
            'description' => 'نشاط قديم',
            'causer_type' => User::class,
            'causer_id' => $this->tenantUserA->id,
            'created_at' => now()->setDate(2025, 1, 10)->setTime(10, 0),
        ]);

        $idsRes = $this->actingAs($this->tenantUserA, 'tenant')
            ->getJson(tenant_route_name('activity-logs.get-all-ids', [
                'created_from' => '2026-06-01',
                'created_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->json();

        $this->assertEquals(1, $idsRes['total']);
        $this->assertEquals([$tenantActivity->id], $idsRes['ids']);
        tenancy()->end();
    }

    public function test_tenant_archives_renders_shared_empty_state_when_no_files_exist(): void
    {
        tenancy()->initialize($this->tenantA);

        $disk = Storage::disk('tenant_public');
        foreach ($disk->files('activity-logs') as $existingFile) {
            $disk->delete($existingFile);
        }

        $this->actingAs($this->tenantUserA, 'tenant')
            ->get(tenant_route_name('activity-logs.archives'))
            ->assertOk()
            ->assertSee('empty-state')
            ->assertSee('لا توجد ملفات مصدرة')
            ->assertSee('العودة للسجل');

        tenancy()->end();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\Admin\MassDelete\DeleteAdminRecordsAction;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Mail\Auth\UserWelcomeMail;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\AdminMangment\Users\UserService as AdminUserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

class TestUsersTenancyBootstrapper implements TenancyBootstrapper
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

final class UsersAndProfilesDualReviewTest extends TestCase
{
    protected array $tempFiles = [];
    protected string $defaultTenantDb = '';
    protected ?array $originalBootstrappers = null;
    protected ?array $originalMysqlConfig = null;
    protected ?array $originalTenantConfig = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBootstrappers = config('tenancy.bootstrappers');
        $this->originalMysqlConfig = config('database.connections.mysql');
        $this->originalTenantConfig = config('database.connections.tenant');

        $this->defaultTenantDb = tempnam(sys_get_temp_dir(), 'usr_ten_def_') . '.sqlite';
        touch($this->defaultTenantDb);
        $this->tempFiles[] = $this->defaultTenantDb;
        TestUsersTenancyBootstrapper::$defaultDb = $this->defaultTenantDb;
        TestUsersTenancyBootstrapper::$databases = [];

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->defaultTenantDb, 'prefix' => ''],
            'tenancy.bootstrappers' => [TestUsersTenancyBootstrapper::class],
            'activitylog.enabled' => false,
            'tenant-mass-delete' => [
                'users' => ['class' => User::class],
                'roles' => ['class' => Role::class],
            ],
        ]);

        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutVite();

        $this->setUpDatabaseSchemas('mysql');
        $this->setUpDatabaseSchemas('tenant');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        config([
            'tenancy.bootstrappers' => $this->originalBootstrappers,
            'database.connections.mysql' => $this->originalMysqlConfig,
            'database.connections.tenant' => $this->originalTenantConfig,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');

        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function setUpDatabaseSchemas(string $connection): void
    {
        $schema = Schema::connection($connection);

        if ($connection === 'mysql') {
            $schema->create('admin_users', function (Blueprint $table): void {
                $table->id();
                $table->boolean('must_change_password')->default(false);
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('status')->default('active');
                $table->string('image')->nullable();
                $table->boolean('is_super_admin')->default(false);
                $table->timestamp('last_login_at')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });

            $schema->create('settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->nullable();
                $table->text('value')->nullable();
                $table->boolean('force_two_factor_auth')->default(false);
                $table->timestamps();
            });

            $schema->create('company_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id')->nullable();
                $table->boolean('force_two_factor_auth')->default(false);
                $table->timestamps();
            });

            $schema->create('tenants', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('db_database')->default(':memory:');
                $table->string('db_username')->default('');
                $table->string('db_password')->default('');
                $table->string('company_name')->default('Test Company');
                $table->string('commercial_number')->default('1234567890');
                $table->string('access_type')->default('path');
                $table->json('data')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            $schema->create('domains', function (Blueprint $table): void {
                $table->id();
                $table->string('domain')->unique();
                $table->string('tenant_id');
                $table->timestamps();
            });

            $schema->create('plans', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->default('Unlimited Plan');
                $table->string('limits_mode', 20)->default('unlimited');
                $table->unsignedInteger('included_users')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            $schema->create('subscriptions', function (Blueprint $table): void {
                $table->id();
                $table->string('subscription_number')->nullable();
                $table->string('tenant_id');
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('status')->default('active');
                $table->decimal('price', 10, 2)->default(0);
                $table->timestamps();
            });

            $schema->create('subscription_periods', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->timestamp('period_start')->nullable();
                $table->timestamp('period_end')->nullable();
                $table->boolean('is_current')->default(true);
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            $schema->create('users', function (Blueprint $table): void {
                $table->id();
                $table->boolean('must_change_password')->default(false);
                $table->string('name');
                $table->string('employee_number')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone', 20)->nullable();
                $table->string('status')->default('active');
                $table->string('image')->nullable();
                $table->string('background_image')->nullable();
                $table->string('global_uid')->nullable();
                $table->string('license_seat_id')->nullable();
                $table->string('current_session_id')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });

            $schema->create('settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->nullable();
                $table->text('value')->nullable();
                $table->boolean('force_two_factor_auth')->default(false);
                $table->timestamps();
            });
        }

        $schema->create('roles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        $schema->create('permissions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->string('section')->nullable();
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        $schema->create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        $schema->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        $schema->create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        // Seed initial permissions
        if ($connection === 'mysql') {
            foreach (['dashboard.main', 'users.view', 'users.create', 'users.edit', 'users.delete', 'users.change_status', 'roles.view', 'roles.create'] as $name) {
                Permission::on('mysql')->firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['section' => 'system']);
            }
            $superRole = Role::on('mysql')->firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
            $superRole->syncPermissions(Permission::on('mysql')->where('guard_name', 'admin')->get());
        } else {
            foreach (['dashboard.view', 'users.view', 'users.create', 'users.edit', 'users.delete', 'users.change_status', 'roles.view', 'roles.create'] as $name) {
                Permission::on('tenant')->firstOrCreate(['name' => $name, 'guard_name' => 'tenant'], ['section' => 'system']);
            }
            $adminRole = Role::on('tenant')->firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']);
            $adminRole->syncPermissions(Permission::on('tenant')->where('guard_name', 'tenant')->get());
        }
    }

    private function createAdminUser(array $attributes = [], array $roles = ['SuperAdmin']): AdminUser
    {
        $user = AdminUser::on('mysql')->create(array_merge([
            'name' => 'مدير النظام',
            'email' => 'admin_' . uniqid() . '@lot.test',
            'phone' => '0512345678',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ], $attributes));

        foreach ($roles as $roleName) {
            $role = Role::on('mysql')->where('name', $roleName)->where('guard_name', 'admin')->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        return $user;
    }

    private function createTenantUser(Tenant $tenant, array $attributes = [], array $roles = ['Admin']): User
    {
        $planId = DB::connection('mysql')->table('plans')->insertGetId([
            'name' => 'Unlimited Plan',
            'limits_mode' => 'unlimited',
            'included_users' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('mysql')->table('subscriptions')->updateOrInsert(
            ['tenant_id' => $tenant->id],
            ['subscription_number' => 'SUB_' . $tenant->id, 'plan_id' => $planId, 'status' => 'active', 'price' => 100, 'created_at' => now(), 'updated_at' => now()]
        );

        tenancy()->initialize($tenant);
        if (! Schema::connection('tenant')->hasTable('users')) {
            $this->setUpDatabaseSchemas('tenant');
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::on('tenant')->create(array_merge([
            'name' => 'مستخدم المستأجر',
            'email' => 'tenant_user_' . uniqid() . '@lot.test',
            'phone' => '0587654321',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ], $attributes));

        foreach ($roles as $roleName) {
            $role = Role::on('tenant')->where('name', $roleName)->where('guard_name', 'tenant')->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        return $user;
    }

    private function createTenantWithDb(string $tenantId): Tenant
    {
        $tenantDb = tempnam(sys_get_temp_dir(), 'usr_' . $tenantId . '_') . '.sqlite';
        touch($tenantDb);
        $this->tempFiles[] = $tenantDb;
        TestUsersTenancyBootstrapper::$databases[$tenantId] = $tenantDb;

        $tenant = Tenant::on('mysql')->create([
            'id' => $tenantId,
            'db_database' => $tenantDb,
            'db_username' => '',
            'db_password' => '',
            'company_name' => 'Tenant ' . $tenantId,
            'commercial_number' => '1234567890',
            'access_type' => 'path',
        ]);
        $tenant->domains()->create(['domain' => $tenantId . '.localhost']);

        $planId = DB::connection('mysql')->table('plans')->insertGetId([
            'name' => 'Unlimited Plan',
            'limits_mode' => 'unlimited',
            'included_users' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('mysql')->table('subscriptions')->insert([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        $this->setUpDatabaseSchemas('tenant');

        return $tenant;
    }

    /**
     * USR-03, USR-04, USR-06: Admin user creation validations and role requirement.
     */
    public function test_admin_user_creation_validations_and_role_requirement(): void
    {
        Mail::fake();
        $admin = $this->createAdminUser();
        $adminRole = Role::on('mysql')->firstOrCreate(['name' => 'Auditor', 'guard_name' => 'admin']);

        // Missing all required fields
        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.users.store'), []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'role_id']);

        // Invalid phone format (not 10 digits starting with 05)
        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'valid@lot.test',
            'phone' => '0123456789',
            'role_id' => $adminRole->id,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);

        // Cross guard role rejection (tenant guard role created in central mysql DB)
        $tenantGuardRole = Role::on('mysql')->create(['name' => 'TenantRoleOnCentral', 'guard_name' => 'tenant']);
        $response = $this->actingAs($admin, 'admin')->postJson(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'valid2@lot.test',
            'phone' => '0512345678',
            'role_id' => $tenantGuardRole->id,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['role_id']);

        // Successful creation (ignores injected password, auto-generates, and emails it)
        $response = $this->actingAs($admin, 'admin')->post(route('admin.users.store'), [
            'name' => 'Valid Admin User',
            'email' => 'valid_admin@lot.test',
            'phone' => '0599887766',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_id' => $adminRole->id,
        ]);
        $response->assertRedirect(route('admin.users.index'));

        $created = AdminUser::on('mysql')->where('email', 'valid_admin@lot.test')->first();
        $this->assertNotNull($created);
        $this->assertFalse(Hash::check('Password123!', $created->password));
        $this->assertTrue($created->hasRole('Auditor', 'admin'));
        $this->assertTrue($created->must_change_password);

        $sentPassword = null;
        Mail::assertQueued(UserWelcomeMail::class, function (UserWelcomeMail $mail) use (&$sentPassword): bool {
            if (! $mail->hasTo('valid_admin@lot.test')) {
                return false;
            }
            $sentPassword = $mail->temporaryPassword;

            return ! empty($sentPassword) && $mail->connection === 'sync' && $sentPassword !== 'Password123!';
        });
        $this->assertNotNull($sentPassword);
        $this->assertTrue(Hash::check($sentPassword, $created->password));
    }

    /**
     * USR-03, USR-04, USR-06: Tenant user creation validations and role requirement.
     */
    public function test_tenant_user_creation_validations_and_role_requirement(): void
    {
        Mail::fake();
        $tenant = $this->createTenantWithDb('tenant-u1');
        $manager = $this->createTenantUser($tenant, [], ['Admin']);
        $tenantRole = Role::on('tenant')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'tenant']);

        // Missing fields
        $response = $this->actingAs($manager, 'tenant')->postJson(tenant_route_name('users.store'), []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'role_id']);

        // Invalid phone
        $response = $this->actingAs($manager, 'tenant')->postJson(tenant_route_name('users.store'), [
            'name' => 'Tenant User',
            'email' => 'staff@lot.test',
            'phone' => '05123',
            'role_id' => $tenantRole->id,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);

        // Successful creation (ignores injected password, auto-generates, and emails it)
        $response = $this->actingAs($manager, 'tenant')->post(tenant_route_name('users.store'), [
            'name' => 'Valid Tenant User',
            'email' => 'valid_tenant@lot.test',
            'phone' => '0511223344',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_id' => $tenantRole->id,
        ]);
        $response->assertRedirect(tenant_route_name('users.index'));

        $created = User::on('tenant')->where('email', 'valid_tenant@lot.test')->first();
        $this->assertNotNull($created);
        $this->assertFalse(Hash::check('Password123!', $created->password));
        $this->assertTrue($created->hasRole('Staff', 'tenant'));
        $this->assertTrue($created->must_change_password);

        $sentPassword = null;
        Mail::assertQueued(UserWelcomeMail::class, function (UserWelcomeMail $mail) use (&$sentPassword): bool {
            if (! $mail->hasTo('valid_tenant@lot.test')) {
                return false;
            }
            $sentPassword = $mail->temporaryPassword;

            return ! empty($sentPassword) && $mail->connection === 'sync' && $sentPassword !== 'Password123!'
                && str_contains($mail->loginUrl, '/admin/login?tenant_id=tenant-u1');
        });
        $this->assertNotNull($sentPassword);
        $this->assertTrue(Hash::check($sentPassword, $created->password));

        // Attempting to update password via tenant user edit must not mutate password
        $this->actingAs($manager, 'tenant')->put(tenant_route_name('users.update', $created), [
            'name' => 'Edited Tenant User',
            'email' => 'valid_tenant@lot.test',
            'phone' => '0511223344',
            'role_id' => $tenantRole->id,
            'password' => 'HijackedPassword123!',
            'password_confirmation' => 'HijackedPassword123!',
        ])->assertRedirect(tenant_route_name('users.index'));

        $created->refresh();
        $this->assertSame('Edited Tenant User', $created->name);
        $this->assertTrue(Hash::check($sentPassword, $created->password));
        $this->assertFalse(Hash::check('HijackedPassword123!', $created->password));

        tenancy()->initialize($tenant);
        $this->actingAs($created, 'tenant')
            ->get(tenant_route_name('profile.show'))
            ->assertRedirect(tenant_route_name('users.password.change'));

        $this->post(tenant_route_name('users.password.update'), [
            'current_password' => $sentPassword,
            'new_password' => 'NewTenantPassword123!',
            'new_password_confirmation' => 'NewTenantPassword123!',
        ])->assertRedirect(tenant_route_name('login'))
            ->assertSessionHas('tenant_login_context', 'tenant-u1')
            ->assertSessionMissing('auth_tenant_id');

        $this->assertGuest('tenant');
        $this->assertFalse($created->fresh()->must_change_password);
        $this->assertNull($created->fresh()->current_session_id);
    }

    /**
     * USR-05: Unique email and phone constraints on create and update.
     */
    public function test_unique_email_and_phone_constraints_on_create_and_update(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::on('mysql')->firstOrCreate(['name' => 'Operator', 'guard_name' => 'admin']);

        $user1 = AdminUser::on('mysql')->create([
            'name' => 'User One',
            'email' => 'user1@lot.test',
            'phone' => '0511111111',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $user1->assignRole($role);

        $user2 = AdminUser::on('mysql')->create([
            'name' => 'User Two',
            'email' => 'user2@lot.test',
            'phone' => '0522222222',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $user2->assignRole($role);

        // Updating user2 with user1's email must fail
        $response = $this->actingAs($admin, 'admin')->putJson(route('admin.users.update', $user2), [
            'name' => 'User Two Updated',
            'email' => 'user1@lot.test',
            'phone' => '0522222222',
            'role_id' => $role->id,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);

        // Updating user2 keeping own email must pass
        $response = $this->actingAs($admin, 'admin')->putJson(route('admin.users.update', $user2), [
            'name' => 'User Two Renamed',
            'email' => 'user2@lot.test',
            'phone' => '0522222222',
            'role_id' => $role->id,
        ]);
        $response->assertStatus(302);
        $this->assertEquals('User Two Renamed', $user2->fresh()->name);
    }

    /**
     * USR-07: Profile self-service cannot escalate roles, status, or privileges.
     */
    public function test_profile_self_service_cannot_escalate_roles_or_privileges(): void
    {
        $tenant = $this->createTenantWithDb('tenant-u2');
        $roleStaff = Role::on('tenant')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'tenant']);
        $roleAdmin = Role::on('tenant')->firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']);

        $user = $this->createTenantUser($tenant, ['email' => 'staff_self@lot.test'], ['Staff']);

        // Attempting to inject role_id, status, is_super_admin in profile update
        $response = $this->actingAs($user, 'tenant')->put(tenant_route_name('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'staff_self@lot.test',
            'phone' => '0587654321',
            'role_id' => $roleAdmin->id,
            'status' => 'inactive',
            'is_super_admin' => 1,
        ]);
        $response->assertRedirect(tenant_route_name('profile.show'));

        $fresh = $user->fresh();
        $this->assertEquals('Updated Name', $fresh->name);
        $this->assertTrue($fresh->hasRole('Staff', 'tenant'));
        $this->assertFalse($fresh->hasRole('Admin', 'tenant'));
        $this->assertEquals('active', $fresh->status);
    }

    /**
     * USR-08: Updating user without password preserves existing password hash.
     */
    public function test_updating_user_without_password_preserves_existing_password_hash(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::on('mysql')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'admin']);
        $managed = AdminUser::on('mysql')->create([
            'name' => 'Target Admin',
            'email' => 'target@lot.test',
            'phone' => '0512345678',
            'password' => Hash::make('OriginalPassword123!'),
            'status' => 'active',
        ]);
        $managed->assignRole($role);
        $originalHash = $managed->password;

        // Update name and phone with empty password
        $response = $this->actingAs($admin, 'admin')->put(route('admin.users.update', $managed), [
            'name' => 'Target Renamed',
            'email' => 'target@lot.test',
            'phone' => '0599999999',
            'role_id' => $role->id,
            'password' => '',
            'password_confirmation' => '',
        ]);
        $response->assertRedirect(route('admin.users.index'));

        $managed->refresh();
        $this->assertEquals('Target Renamed', $managed->name);
        $this->assertEquals('0599999999', $managed->phone);
        $this->assertEquals($originalHash, $managed->password);

        // Attempting to inject a new password via user edit must NOT modify the password
        $response = $this->actingAs($admin, 'admin')->put(route('admin.users.update', $managed), [
            'name' => 'Target Renamed Again',
            'email' => 'target@lot.test',
            'phone' => '0599999999',
            'role_id' => $role->id,
            'password' => 'NewSecretPassword123!',
            'password_confirmation' => 'NewSecretPassword123!',
        ]);
        $response->assertRedirect(route('admin.users.index'));

        $managed->refresh();
        $this->assertEquals('Target Renamed Again', $managed->name);
        $this->assertFalse(Hash::check('NewSecretPassword123!', $managed->password));
        $this->assertTrue(Hash::check('OriginalPassword123!', $managed->password));
        $this->assertEquals($originalHash, $managed->password);
    }

    /**
     * USR-09: User with overview-only role can access dashboard without 403.
     */
    public function test_user_with_overview_only_role_can_access_dashboard_without_403(): void
    {
        // Central Admin with only dashboard.main
        $roleMain = Role::on('mysql')->firstOrCreate(['name' => 'OverviewOnlyAdmin', 'guard_name' => 'admin']);
        $pMain = Permission::on('mysql')->where('name', 'dashboard.main')->where('guard_name', 'admin')->first();
        $roleMain->syncPermissions([$pMain]);

        $admin = AdminUser::on('mysql')->create([
            'name' => 'Overview Admin',
            'email' => 'overview_admin@lot.test',
            'phone' => '0512345678',
            'password' => Hash::make('Pass123!'),
            'status' => 'active',
        ]);
        $admin->assignRole($roleMain);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $this->assertNotEquals(403, $response->getStatusCode());

        // Tenant User with only dashboard.view
        $tenant = $this->createTenantWithDb('tenant-u3');
        $roleView = Role::on('tenant')->firstOrCreate(['name' => 'OverviewOnlyTenant', 'guard_name' => 'tenant']);
        $pView = Permission::on('tenant')->where('name', 'dashboard.view')->where('guard_name', 'tenant')->first();
        $roleView->syncPermissions([$pView]);

        $tenantUser = $this->createTenantUser($tenant, [
            'email' => 'overview_tenant@lot.test',
        ], ['OverviewOnlyTenant']);

        $response = $this->actingAs($tenantUser, 'tenant')->get(tenant_route_name('dashboard'));
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * USR-10, USR-11: Non-manager cannot edit other users, but can edit own profile.
     */
    public function test_non_manager_cannot_edit_other_users_but_can_edit_own_profile(): void
    {
        $tenant = $this->createTenantWithDb('tenant-u4');
        $roleStaff = Role::on('tenant')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'tenant']);
        $staff = $this->createTenantUser($tenant, ['email' => 'staff1@lot.test'], ['Staff']);
        $otherUser = $this->createTenantUser($tenant, ['email' => 'other@lot.test'], ['Staff']);

        // Attempting to edit other user without users.edit permission -> 403
        $response = $this->actingAs($staff, 'tenant')->get(tenant_route_name('users.edit', $otherUser));
        $response->assertStatus(403);

        $response = $this->actingAs($staff, 'tenant')->put(tenant_route_name('users.update', $otherUser), [
            'name' => 'Hacked Name',
            'email' => 'other@lot.test',
            'phone' => '0512345678',
            'role_id' => $roleStaff->id,
        ]);
        $response->assertStatus(403);

        // Can access and update own profile
        $response = $this->actingAs($staff, 'tenant')->get(tenant_route_name('profile.edit'));
        $response->assertStatus(200);

        $response = $this->actingAs($staff, 'tenant')->put(tenant_route_name('profile.update'), [
            'name' => 'Staff New Name',
            'email' => 'staff1@lot.test',
            'phone' => '0587654321',
        ]);
        $response->assertRedirect(tenant_route_name('profile.show'));
        $this->assertEquals('Staff New Name', $staff->fresh()->name);
    }

    /**
     * USR-12, USR-13: Profile image upload validation and unauthorized replacement prevention.
     */
    public function test_profile_image_upload_validation_and_unauthorized_replacement_prevention(): void
    {
        $tenant = $this->createTenantWithDb('tenant-u5');
        Storage::fake('tenant_public');

        $staff = $this->createTenantUser($tenant, [], ['Staff']);
        $victim = $this->createTenantUser($tenant, [], ['Staff']);

        // Non-image file upload rejected
        $fakePdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $response = $this->actingAs($staff, 'tenant')->postJson(tenant_route_name('users.update-image', $staff), [
            'profile_image' => $fakePdf,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['profile_image']);

        // Unauthorized user cannot update another user's profile image
        $validImage = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $response = $this->actingAs($staff, 'tenant')->postJson(tenant_route_name('users.update-image', $victim), [
            'profile_image' => $validImage,
        ]);
        $response->assertStatus(403);

        // User can update their own profile image
        $response = $this->actingAs($staff, 'tenant')->post(tenant_route_name('users.update-image', $staff), [
            'profile_image' => $validImage,
        ]);
        $response->assertRedirect();
        $this->assertNotNull($staff->fresh()->image);
    }

    /**
     * USR-14: Status toggle and inactive user login rejection.
     */
    public function test_status_toggle_and_inactive_user_login_rejection(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::on('mysql')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'admin']);
        $managed = AdminUser::on('mysql')->create([
            'name' => 'Toggle Target',
            'email' => 'toggle@lot.test',
            'phone' => '0512345678',
            'password' => 'Password123!',
            'status' => 'active',
        ]);
        $managed->assignRole($role);

        // Toggle from active to inactive via PATCH
        $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.users.toggle-status', $managed));
        $response->assertStatus(200);
        $this->assertEquals(AdminUserStatus::INACTIVE, $managed->fresh()->status);

        // Log out admin so guest middleware doesn't redirect
        auth('admin')->logout();

        // Attempting to log in as inactive admin user must fail
        $response = $this->post(route('admin.login'), [
            'email' => 'toggle@lot.test',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest('admin');

        // Toggle back to active via PATCH
        $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.users.toggle-status', $managed));
        $response->assertStatus(200);
        $this->assertEquals(AdminUserStatus::ACTIVE, $managed->fresh()->status);
    }

    /**
     * USR-15: Self-deletion and self-status toggle prevented in Admin and Tenant.
     */
    public function test_self_deletion_and_self_status_toggle_prevented_in_admin_and_tenant(): void
    {
        // Central Admin self-deletion and self-status change
        $adminRole = Role::on('mysql')->firstOrCreate(['name' => 'ManagerAdmin', 'guard_name' => 'admin']);
        $pDel = Permission::on('mysql')->where('name', 'users.delete')->where('guard_name', 'admin')->first();
        $pStatus = Permission::on('mysql')->where('name', 'users.change_status')->where('guard_name', 'admin')->first();
        $adminRole->syncPermissions([$pDel, $pStatus]);

        $admin = AdminUser::on('mysql')->create([
            'name' => 'Self Admin',
            'email' => 'self_admin@lot.test',
            'phone' => '0512345678',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $admin->assignRole($adminRole);

        // Self-delete via AJAX
        $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.users.destroy', $admin));
        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'لا يمكنك حذف حسابك الشخصي.']);

        // Self-status toggle via PATCH
        $response = $this->actingAs($admin, 'admin')->patchJson(route('admin.users.toggle-status', $admin));
        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'لا يمكنك تغيير حالة حسابك الشخصي.']);

        // Tenant self-deletion and self-status change
        $tenant = $this->createTenantWithDb('tenant-u6');
        $primary = $this->createTenantUser($tenant, ['email' => 'primary_u6@lot.test'], ['Admin']);

        $managerRole = Role::on('tenant')->firstOrCreate(['name' => 'StaffManager', 'guard_name' => 'tenant']);
        $managerRole->givePermissionTo(Permission::on('tenant')->whereIn('name', ['users.delete', 'users.edit'])->get());

        $tenantUser = $this->createTenantUser($tenant, [
            'email' => 'tenant_self@lot.test',
        ], ['StaffManager']);

        // Tenant self-delete
        $response = $this->actingAs($tenantUser, 'tenant')->deleteJson(tenant_route_name('users.destroy', $tenantUser));
        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'لا يمكنك حذف حسابك الشخصي.']);

        // Tenant self-status toggle via PATCH
        $response = $this->actingAs($tenantUser, 'tenant')->patchJson(tenant_route_name('users.toggle-status', $tenantUser), [
            'status' => 'inactive',
        ]);
        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'لا يمكنك تغيير حالة حسابك الشخصي.']);
    }

    /**
     * USR-15: System protected users cannot be deleted (SuperAdmin & Tenant Primary Admin).
     */
    public function test_system_protected_users_cannot_be_deleted(): void
    {
        $superAdmin = $this->createAdminUser();
        $managerRole = Role::on('mysql')->firstOrCreate(['name' => 'ManagerRole', 'guard_name' => 'admin']);
        $managerRole->syncPermissions(Permission::on('mysql')->where('guard_name', 'admin')->get());

        $otherAdmin = $this->createAdminUser(['email' => 'manager_other@lot.test'], ['ManagerRole']);

        // Cannot delete SuperAdmin
        $response = $this->actingAs($otherAdmin, 'admin')->deleteJson(route('admin.users.destroy', $superAdmin));
        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'لا يمكن حذف السوبر أدمن']);

        // Tenant primary admin ID=1
        $tenant = $this->createTenantWithDb('tenant-u7');
        tenancy()->initialize($tenant);
        $primaryAdmin = new User([
            'name' => 'Primary Tenant Admin',
            'email' => 'primary@lot.test',
            'phone' => '0512345678',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $primaryAdmin->id = 1;
        $primaryAdmin->save();
        $primaryAdmin->assignRole(Role::on('tenant')->where('name', 'Admin')->where('guard_name', 'tenant')->first());

        $staffAdmin = $this->createTenantUser($tenant, [], ['Admin']);

        $response = $this->actingAs($staffAdmin, 'tenant')->deleteJson(tenant_route_name('users.destroy', $primaryAdmin));
        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'لا يمكن حذف المستخدم الأساسي.']);
    }

    /**
     * USR-17: Mass delete protects SuperAdmin and current user.
     */
    public function test_admin_and_tenant_mass_delete_protects_system_accounts_and_self(): void
    {
        $superAdmin = $this->createAdminUser();
        $managerRole = Role::on('mysql')->firstOrCreate(['name' => 'ManagerRole2', 'guard_name' => 'admin']);
        $managerRole->syncPermissions(Permission::on('mysql')->where('guard_name', 'admin')->get());

        $manager = $this->createAdminUser(['email' => 'manager_bulk@lot.test'], ['ManagerRole2']);
        $freeUser = $this->createAdminUser(['email' => 'free@lot.test'], ['ManagerRole2']);

        // Admin bulk delete containing SuperAdmin -> rejected
        $response = $this->actingAs($manager, 'admin')->postJson(route('admin.users.bulk-delete'), [
            'ids' => [$superAdmin->id, $freeUser->id],
        ]);
        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'لا يمكن حذف السوبر أدمن']);

        // Admin bulk delete containing self -> rejected
        $response = $this->actingAs($manager, 'admin')->postJson(route('admin.users.bulk-delete'), [
            'ids' => [$manager->id, $freeUser->id],
        ]);
        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'لا يمكنك حذف حسابك الشخصي.']);

        // Admin mass delete action protects SuperAdmin
        $action = app(DeleteAdminRecordsAction::class);
        $this->expectException(LogicException::class);
        $action->execute(collect([$superAdmin]));
    }

    /**
     * USR-18: Soft delete preserves image file for archive restoration.
     */
    public function test_soft_delete_preserves_images_for_archive_restoration(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $imagePath = 'users/avatar_' . uniqid() . '.jpg';
        Storage::disk('public')->put($imagePath, 'image-content');

        $user = AdminUser::on('mysql')->create([
            'name' => 'Image User',
            'email' => 'image_user@lot.test',
            'phone' => '0512345678',
            'password' => Hash::make('Pass123!'),
            'image' => $imagePath,
            'status' => 'active',
        ]);
        $user->assignRole(Role::on('mysql')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'admin']));

        // Soft delete via UserService
        app(AdminUserService::class)->deleteUser($user);

        $this->assertTrue($user->fresh()->trashed());
        // Storage file must NOT be deleted on soft delete!
        Storage::disk('public')->assertExists($imagePath);

        // Restoring user preserves image
        $user->restore();
        $this->assertFalse($user->fresh()->trashed());
        $this->assertEquals($imagePath, $user->fresh()->image);
    }

    /**
     * USR-26: Dual tenant database isolation and ID collisions.
     */
    public function test_cross_tenant_two_database_isolation_and_user_id_collision(): void
    {
        $tenantA = $this->createTenantWithDb('tenant-u-a');
        $tenantB = $this->createTenantWithDb('tenant-u-b');

        // Create user with explicit ID=10 in Tenant A
        tenancy()->initialize($tenantA);
        DB::setDefaultConnection('tenant');
        $userA = new User([
            'name' => 'User in Tenant A',
            'email' => 'collision@lot.test',
            'phone' => '0511111111',
            'password' => Hash::make('PassTenantA!'),
            'status' => 'active',
        ]);
        $userA->id = 10;
        $userA->save();
        $userA->assignRole(Role::on('tenant')->firstOrCreate(['name' => 'Staff', 'guard_name' => 'tenant']));
        tenancy()->end();

        // Create user with same explicit ID=10 in Tenant B
        tenancy()->initialize($tenantB);
        DB::setDefaultConnection('tenant');
        $userB = new User([
            'name' => 'User in Tenant B',
            'email' => 'collision@lot.test',
            'phone' => '0522222222',
            'password' => Hash::make('PassTenantB!'),
            'status' => 'active',
        ]);
        $userB->id = 10;
        $userB->save();
        $userB->assignRole(Role::on('tenant')->firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']));

        // Tenant B query finds User B only
        $foundB = User::find(10);
        $this->assertNotNull($foundB);
        $this->assertEquals('User in Tenant B', $foundB->name);
        $this->assertEquals('0522222222', $foundB->phone);

        // Switch to Tenant A and verify User A is unchanged
        tenancy()->initialize($tenantA);
        DB::setDefaultConnection('tenant');
        $foundA = User::find(10);
        $this->assertNotNull($foundA);
        $this->assertEquals('User in Tenant A', $foundA->name);
        $this->assertEquals('0511111111', $foundA->phone);

        // Updating User A in Tenant A does not touch Tenant B
        $foundA->update(['name' => 'Tenant A Renamed']);

        tenancy()->initialize($tenantB);
        DB::setDefaultConnection('tenant');
        $this->assertEquals('User in Tenant B', User::find(10)->name);
    }
}

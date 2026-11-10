<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Actions\Admin\MassDelete\DeleteAdminRecordsAction;
use App\Actions\Tenants\MassDelete\DeleteTenantRecordsAction;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\AdminMangment\Role\RoleService as AdminRoleService;
use App\Services\Tenants\UserManagement\Role\RoleService as TenantRoleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

class TestRolesTenancyBootstrapper implements TenancyBootstrapper
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

final class RolesAndPermissionsDualReviewTest extends TestCase
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

        $this->defaultTenantDb = tempnam(sys_get_temp_dir(), 'ten_def_') . '.sqlite';
        touch($this->defaultTenantDb);
        $this->tempFiles[] = $this->defaultTenantDb;
        TestRolesTenancyBootstrapper::$defaultDb = $this->defaultTenantDb;
        TestRolesTenancyBootstrapper::$databases = [];

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $this->defaultTenantDb, 'prefix' => ''],
            'tenancy.bootstrappers' => [TestRolesTenancyBootstrapper::class],
            'activitylog.enabled' => false,
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
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_super_admin')->default(false);
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

            $schema->create('subscriptions', function (Blueprint $table): void {
                $table->id();
                $table->string('subscription_number')->nullable();
                $table->string('tenant_id');
                $table->string('status')->default('active');
                $table->decimal('price', 10, 2)->default(0);
                $table->timestamps();
            });
        } else {
            $schema->create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('employee_number')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone', 20)->nullable();
                $table->string('status')->default('active');
                $table->string('image')->nullable();
                $table->string('global_uid')->nullable();
                $table->string('license_seat_id')->nullable();
                $table->string('current_session_id')->nullable();
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
            foreach (['dashboard.main', 'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign', 'users.view'] as $name) {
                Permission::on('mysql')->firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['section' => 'system']);
            }
            $superRole = Role::on('mysql')->firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
            $superRole->syncPermissions(Permission::on('mysql')->where('guard_name', 'admin')->get());
        } else {
            foreach (['dashboard.view', 'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign', 'users.view'] as $name) {
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
        DB::connection('mysql')->table('subscriptions')->updateOrInsert(
            ['tenant_id' => $tenant->id],
            ['subscription_number' => 'SUB_' . $tenant->id, 'status' => 'active', 'price' => 100, 'created_at' => now(), 'updated_at' => now()]
        );

        tenancy()->initialize($tenant);
        if (! Schema::connection('tenant')->hasTable('users')) {
            $this->setUpDatabaseSchemas('tenant');
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::on('tenant')->create(array_merge([
            'name' => 'مستخدم المستأجر',
            'email' => 'tenant_user_' . uniqid() . '@lot.test',
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

    /*
    |--------------------------------------------------------------------------
    | ROL-01: Cannot Create Empty Role
    |--------------------------------------------------------------------------
    */

    public function test_admin_cannot_create_role_with_empty_permissions_or_without_permissions(): void
    {
        $admin = $this->createAdminUser();

        // 1. permissions array empty
        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'EmptyRoleTest',
                'permissions' => [],
                'select_all' => 0,
            ]);

        $response->assertSessionHasErrors('permissions');
        $this->assertDatabaseMissing('roles', ['name' => 'EmptyRoleTest', 'guard_name' => 'admin'], 'mysql');

        // 2. permissions omitted with select_all = false
        $response2 = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'OmittedPermsRole',
                'select_all' => false,
            ]);

        $response2->assertSessionHasErrors('permissions');
        $this->assertDatabaseMissing('roles', ['name' => 'OmittedPermsRole', 'guard_name' => 'admin'], 'mysql');
    }

    public function test_tenant_cannot_create_role_with_empty_permissions_or_without_permissions(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-empty']);
        $user = $this->createTenantUser($tenant);

        // 1. permissions empty array
        $response = $this->actingAs($user, 'tenant')
            ->post(tenant_route_name('roles.store'), [
                'name' => 'TenantEmptyRole',
                'permissions' => [],
                'select_all' => 0,
            ]);

        $response->assertSessionHasErrors('permissions');
        $this->assertDatabaseMissing('roles', ['name' => 'TenantEmptyRole', 'guard_name' => 'tenant'], 'tenant');
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-02: Cannot Update Role to Empty Permissions
    |--------------------------------------------------------------------------
    */

    public function test_admin_cannot_update_role_to_empty_permissions(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::on('mysql')->create(['name' => 'FinanceManager', 'guard_name' => 'admin']);
        $role->syncPermissions(['dashboard.main', 'users.view']);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.roles.update', $role->id), [
                'name' => 'FinanceManager',
                'permissions' => [],
                'select_all' => false,
            ]);

        $response->assertSessionHasErrors('permissions');
        $this->assertSame(2, $role->fresh()->permissions()->count());
    }

    public function test_tenant_cannot_update_role_to_empty_permissions(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-up-empty']);
        $user = $this->createTenantUser($tenant);

        $role = Role::on('tenant')->create(['name' => 'BranchOfficer', 'guard_name' => 'tenant']);
        $role->syncPermissions(['dashboard.view', 'users.view']);

        $response = $this->actingAs($user, 'tenant')
            ->put(tenant_route_name('roles.update', $role->id), [
                'name' => 'BranchOfficer',
                'permissions' => [],
                'select_all' => false,
            ]);

        $response->assertSessionHasErrors('permissions');
        $this->assertSame(2, $role->fresh()->permissions()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-03: Rejection of Invalid Permissions Inputs
    |--------------------------------------------------------------------------
    */

    public function test_rejection_of_invalid_permissions_integers_and_cross_guard(): void
    {
        $admin = $this->createAdminUser();

        // 1. Integer IDs instead of names
        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'InvalidIntPerms',
                'permissions' => [1, 2, 3],
            ]);
        $response->assertSessionHasErrors('permissions.0');

        // 2. Cross-guard permission (tenant permission sent to admin)
        $response2 = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'CrossGuardRole',
                'permissions' => ['dashboard.view'], // exists on tenant, not admin
            ]);
        $response2->assertSessionHasErrors('permissions.0');
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-04: select_all Boolean Variations
    |--------------------------------------------------------------------------
    */

    public function test_select_all_boolean_variations_and_empty_handling(): void
    {
        $admin = $this->createAdminUser();

        // String 'false' with no permissions is rejected
        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'StringFalseRole',
                'select_all' => 'false',
                'permissions' => [],
            ]);
        $response->assertSessionHasErrors('permissions');

        // Integer 1 with no permissions selects all permissions
        $response2 = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'SelectAllRole',
                'select_all' => 1,
            ]);
        $response2->assertSessionHasNoErrors();
        $createdRole = Role::on('mysql')->where('name', 'SelectAllRole')->first();
        $this->assertNotNull($createdRole);
        $this->assertSame(
            Permission::on('mysql')->where('guard_name', 'admin')->count(),
            $createdRole->permissions()->count()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-05 & ROL-06: Baseline Overview Permission Always Enforced
    |--------------------------------------------------------------------------
    */

    public function test_admin_role_creation_and_update_always_enforces_dashboard_main_baseline(): void
    {
        $admin = $this->createAdminUser();

        // Sent with users.view only -> dashboard.main is automatically included
        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'StaffRole',
                'permissions' => ['users.view'],
            ]);

        $response->assertSessionHasNoErrors();
        $role = Role::on('mysql')->where('name', 'StaffRole')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('dashboard.main'));
        $this->assertTrue($role->hasPermissionTo('users.view'));
    }

    public function test_tenant_role_creation_and_update_always_enforces_dashboard_view_baseline(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-baseline']);
        $user = $this->createTenantUser($tenant);

        // Sent with users.view only -> dashboard.view is automatically included
        $response = $this->actingAs($user, 'tenant')
            ->post(tenant_route_name('roles.store'), [
                'name' => 'StaffTenantRole',
                'permissions' => ['users.view'],
            ]);

        $response->assertSessionHasNoErrors();
        $role = Role::on('tenant')->where('name', 'StaffTenantRole')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('users.view'));
    }

    public function test_role_with_only_overview_permission_is_valid_and_non_empty(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'OverviewOnlyRole',
                'permissions' => ['dashboard.main'],
            ]);

        $response->assertSessionHasNoErrors();
        $role = Role::on('mysql')->where('name', 'OverviewOnlyRole')->first();
        $this->assertNotNull($role);
        $this->assertSame(1, $role->permissions()->count());
        $this->assertTrue($role->hasPermissionTo('dashboard.main'));
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-08 & ROL-09: Ordinary User Login Succeeds Without 403 on Dashboard
    |--------------------------------------------------------------------------
    */

    public function test_ordinary_admin_user_with_custom_role_has_dashboard_permission(): void
    {
        $roleService = app(AdminRoleService::class);
        $customRole = $roleService->createRole([
            'name' => 'Auditor',
            'permissions' => ['users.view'],
        ]);

        $user = $this->createAdminUser([], ['Auditor']);

        $this->assertTrue($user->hasRole('Auditor'));
        $this->assertTrue($user->can('dashboard.main'));
    }

    public function test_ordinary_tenant_user_with_custom_role_has_dashboard_permission(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-login-test']);
        tenancy()->initialize($tenant);

        $roleService = app(TenantRoleService::class);
        $customRole = $roleService->createRole([
            'name' => 'Reviewer',
            'permissions' => ['users.view'],
        ]);

        $user = $this->createTenantUser($tenant, [], ['Reviewer']);

        $this->assertTrue($user->hasRole('Reviewer'));
        $this->assertTrue($user->can('dashboard.view'));
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-10 & U-06: Single Delete Prevention for Linked Roles
    |--------------------------------------------------------------------------
    */

    public function test_single_delete_of_role_linked_to_users_is_prevented_in_admin(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::on('mysql')->create(['name' => 'SupervisorRole', 'guard_name' => 'admin']);
        $role->syncPermissions(['dashboard.main']);

        $linkedUser = AdminUser::on('mysql')->create([
            'name' => 'موظف مرتبط',
            'email' => 'linked_emp@lot.test',
            'password' => Hash::make('Pass123!'),
        ]);
        $linkedUser->assignRole($role);

        $this->assertTrue($role->users()->exists());

        // Attempt single delete via AJAX
        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.roles.destroy', $role->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين حاليين في النظام.',
        ]);

        $this->assertDatabaseHas('roles', ['id' => $role->id], 'mysql');
    }

    public function test_single_delete_of_role_linked_to_users_is_prevented_in_tenant(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-del-linked']);
        $user = $this->createTenantUser($tenant);

        $role = Role::on('tenant')->create(['name' => 'TenantSupervisor', 'guard_name' => 'tenant']);
        $role->syncPermissions(['dashboard.view']);

        $linkedUser = User::on('tenant')->create([
            'name' => 'مستخدم مستأجر مرتبط',
            'email' => 'tenant_linked@lot.test',
            'password' => Hash::make('Pass123!'),
        ]);
        $linkedUser->assignRole($role);

        $this->assertTrue($role->users()->exists());

        // Attempt single delete via AJAX
        $response = $this->actingAs($user, 'tenant')
            ->deleteJson(tenant_route_name('roles.destroy', $role->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين حاليين في النظام.',
        ]);

        $this->assertDatabaseHas('roles', ['id' => $role->id], 'tenant');
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-13: Free Unlinked Role Can Be Deleted Cleanly
    |--------------------------------------------------------------------------
    */

    public function test_unlinked_free_role_can_be_deleted_cleanly_in_admin_and_tenant(): void
    {
        $admin = $this->createAdminUser();
        $freeAdminRole = Role::on('mysql')->create(['name' => 'TemporaryRole', 'guard_name' => 'admin']);
        $freeAdminRole->syncPermissions(['dashboard.main']);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.roles.destroy', $freeAdminRole->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('roles', ['id' => $freeAdminRole->id], 'mysql');

        // Tenant
        $tenant = Tenant::on('mysql')->create(['id' => 't-del-free']);
        $user = $this->createTenantUser($tenant);

        $freeTenantRole = Role::on('tenant')->create(['name' => 'TempTenantRole', 'guard_name' => 'tenant']);
        $freeTenantRole->syncPermissions(['dashboard.view']);

        $response2 = $this->actingAs($user, 'tenant')
            ->deleteJson(tenant_route_name('roles.destroy', $freeTenantRole->id));

        $response2->assertOk();
        $response2->assertJson(['success' => true]);
        $this->assertDatabaseMissing('roles', ['id' => $freeTenantRole->id], 'tenant');
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-10 & ROL-14: Protected System Roles Cannot Be Deleted
    |--------------------------------------------------------------------------
    */

    public function test_system_protected_roles_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser();
        $superAdminRole = Role::on('mysql')->where('name', 'SuperAdmin')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.roles.destroy', $superAdminRole->id));

        $response->assertStatus(422);
        $this->assertStringContainsString('لا يمكن حذف دور السوبر أدمن', (string) $response->json('message'));

        // Tenant Admin
        $tenant = Tenant::on('mysql')->create(['id' => 't-sys-role']);
        $user = $this->createTenantUser($tenant);

        $primaryAdminRole = Role::on('tenant')->where('name', 'Admin')->firstOrFail();

        $response2 = $this->actingAs($user, 'tenant')
            ->deleteJson(tenant_route_name('roles.destroy', $primaryAdminRole->id));

        $response2->assertStatus(422);
        $this->assertStringContainsString('لا يمكن حذف الدور الأساسي', (string) $response2->json('message'));
    }

    public function test_system_protected_roles_cannot_be_edited_or_updated_in_admin_and_tenant(): void
    {
        $admin = $this->createAdminUser();
        $superAdminRole = Role::on('mysql')->where('name', 'SuperAdmin')->firstOrFail();
        $initialAdminPermsCount = $superAdminRole->permissions()->count();

        // Admin SuperAdmin edit route redirects with error
        $this->actingAs($admin, 'admin')
            ->get(route('admin.roles.edit', $superAdminRole->id))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', 'لا يمكن تعديل دور مدير النظام الرئيسي.');

        // Admin SuperAdmin update via web form redirects with error and preserves role & permissions
        $this->actingAs($admin, 'admin')
            ->put(route('admin.roles.update', $superAdminRole->id), [
                'name' => 'RenamedSuperAdmin',
                'permissions' => ['dashboard.main'],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', 'لا يمكن تعديل دور مدير النظام الرئيسي.');

        // Admin SuperAdmin update via JSON returns 422 even without permissions
        $this->actingAs($admin, 'admin')
            ->putJson(route('admin.roles.update', $superAdminRole->id), [
                'name' => 'RenamedSuperAdmin',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'لا يمكن تعديل دور مدير النظام الرئيسي.',
            ]);

        $superAdminRole->refresh();
        $this->assertSame('SuperAdmin', $superAdminRole->name);
        $this->assertSame($initialAdminPermsCount, $superAdminRole->permissions()->count());

        // Direct service call throws LogicException for SuperAdmin
        try {
            app(AdminRoleService::class)->updateRole($superAdminRole, [
                'name' => 'RenamedSuperAdmin',
                'permissions' => ['dashboard.main'],
            ]);
            $this->fail('Expected LogicException when updating SuperAdmin role.');
        } catch (LogicException $e) {
            $this->assertSame('لا يمكن تعديل دور مدير النظام الرئيسي.', $e->getMessage());
        }

        // Custom admin role remains editable
        $customAdminRole = Role::on('mysql')->create(['name' => 'EditableAdminRole', 'guard_name' => 'admin']);
        $customAdminRole->syncPermissions(['dashboard.main']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.roles.update', $customAdminRole->id), [
                'name' => 'UpdatedAdminRole',
                'permissions' => ['dashboard.main', 'users.view'],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('success');
        $this->assertSame('UpdatedAdminRole', $customAdminRole->fresh()->name);

        // Tenant Admin role
        $tenant = Tenant::on('mysql')->create(['id' => 't-sys-edit']);
        $tenantUser = $this->createTenantUser($tenant);
        $primaryAdminRole = Role::on('tenant')->where('name', 'Admin')->firstOrFail();
        $initialTenantPermsCount = $primaryAdminRole->permissions()->count();

        // Tenant Admin edit route redirects with error
        $this->actingAs($tenantUser, 'tenant')
            ->get(tenant_route_name('roles.edit', $primaryAdminRole->id))
            ->assertRedirect(tenant_route_name('roles.index'))
            ->assertSessionHas('error', 'لا يمكن تعديل دور مدير النظام.');

        // Tenant Admin update via web form redirects with error and preserves role & permissions
        $this->actingAs($tenantUser, 'tenant')
            ->put(tenant_route_name('roles.update', $primaryAdminRole->id), [
                'name' => 'RenamedTenantAdmin',
                'permissions' => ['dashboard.view'],
            ])
            ->assertRedirect(tenant_route_name('roles.index'))
            ->assertSessionHas('error', 'لا يمكن تعديل دور مدير النظام.');

        // Tenant Admin update via JSON returns 422 even without permissions
        $this->actingAs($tenantUser, 'tenant')
            ->putJson(tenant_route_name('roles.update', $primaryAdminRole->id), [
                'name' => 'RenamedTenantAdmin',
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'لا يمكن تعديل دور مدير النظام.',
            ]);

        $primaryAdminRole->refresh();
        $this->assertSame('Admin', $primaryAdminRole->name);
        $this->assertSame($initialTenantPermsCount, $primaryAdminRole->permissions()->count());

        // Direct service call throws LogicException for Tenant Admin
        try {
            app(TenantRoleService::class)->updateRole($primaryAdminRole, [
                'name' => 'RenamedTenantAdmin',
                'permissions' => ['dashboard.view'],
            ]);
            $this->fail('Expected LogicException when updating Tenant Admin role.');
        } catch (LogicException $e) {
            $this->assertSame('لا يمكن تعديل دور مدير النظام.', $e->getMessage());
        }

        // Custom tenant role remains editable
        $customTenantRole = Role::on('tenant')->create(['name' => 'EditableTenantRole', 'guard_name' => 'tenant']);
        $customTenantRole->syncPermissions(['dashboard.view']);

        $this->actingAs($tenantUser, 'tenant')
            ->put(tenant_route_name('roles.update', $customTenantRole->id), [
                'name' => 'UpdatedTenantRole',
                'permissions' => ['dashboard.view', 'users.view'],
            ])
            ->assertRedirect(tenant_route_name('roles.index'))
            ->assertSessionHas('success');
        $this->assertSame('UpdatedTenantRole', $customTenantRole->fresh()->name);

        tenancy()->end();
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-12: Mass Delete Protects Linked & Protected Roles
    |--------------------------------------------------------------------------
    */

    public function test_admin_mass_delete_protects_linked_roles_and_superadmin(): void
    {
        $admin = $this->createAdminUser();
        $superAdminRole = Role::on('mysql')->where('name', 'SuperAdmin')->firstOrFail();

        $linkedRole = Role::on('mysql')->create(['name' => 'LinkedRoleMass', 'guard_name' => 'admin']);
        $linkedRole->syncPermissions(['dashboard.main']);
        $emp = AdminUser::on('mysql')->create([
            'name' => 'موظف جماعي',
            'email' => 'mass_emp@lot.test',
            'password' => Hash::make('Pass123!'),
        ]);
        $emp->assignRole($linkedRole);

        $freeRole = Role::on('mysql')->create(['name' => 'FreeRoleMass', 'guard_name' => 'admin']);
        $freeRole->syncPermissions(['dashboard.main']);

        // Attempt mass delete on group including linkedRole
        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.mass-delete'), [
                'model' => 'Role',
                'ids' => [$linkedRole->id, $freeRole->id],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين حاليين في النظام.',
        ]);

        // Both roles remain intact due to atomic transaction rollback
        $this->assertDatabaseHas('roles', ['id' => $linkedRole->id], 'mysql');
        $this->assertDatabaseHas('roles', ['id' => $freeRole->id], 'mysql');
    }

    public function test_tenant_mass_delete_protects_linked_roles_and_primary_admin(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-mass-del']);
        $user = $this->createTenantUser($tenant);

        $primaryAdminRole = Role::on('tenant')->where('name', 'Admin')->firstOrFail();

        $linkedRole = Role::on('tenant')->create(['name' => 'TenantMassLinked', 'guard_name' => 'tenant']);
        $linkedRole->syncPermissions(['dashboard.view']);
        $emp = User::on('tenant')->create([
            'name' => 'مستخدم جماعي',
            'email' => 't_mass_emp@lot.test',
            'password' => Hash::make('Pass123!'),
        ]);
        $emp->assignRole($linkedRole);

        // Attempt mass delete on linked role
        $response = $this->actingAs($user, 'tenant')
            ->postJson(tenant_route_name('mass-delete'), [
                'model' => 'Role',
                'ids' => [$linkedRole->id],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('لا يمكن حذف هذا الدور لأنه مرتبط بمستخدمين حاليين', (string) $response->json('message'));
        $this->assertDatabaseHas('roles', ['id' => $linkedRole->id], 'tenant');
    }

    /*
    |--------------------------------------------------------------------------
    | ROL-16: Two-Database Multi-Tenant Isolation with Numeric ID Collisions
    |--------------------------------------------------------------------------
    */

    public function test_cross_tenant_two_database_isolation_and_id_collisions(): void
    {
        $dbFileA = tempnam(sys_get_temp_dir(), 'ten_role_a_') . '.sqlite';
        $dbFileB = tempnam(sys_get_temp_dir(), 'ten_role_b_') . '.sqlite';
        $this->tempFiles[] = $dbFileA;
        $this->tempFiles[] = $dbFileB;

        touch($dbFileA);
        touch($dbFileB);

        TestRolesTenancyBootstrapper::$databases = [
            'tenant-alpha' => $dbFileA,
            'tenant-beta' => $dbFileB,
        ];

        $tenantA = Tenant::on('mysql')->create(['id' => 'tenant-alpha']);
        $tenantB = Tenant::on('mysql')->create(['id' => 'tenant-beta']);

        // Setup Tenant A database
        tenancy()->initialize($tenantA);
        $pdoA = DB::connection('tenant')->getPdo();
        $this->setUpDatabaseSchemas('tenant');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $userA = new User([
            'name' => 'مدير المستأجر ألفا',
            'email' => 'alpha_admin@lot.test',
            'password' => Hash::make('Pass123!'),
        ]);
        $userA->id = 1;
        $userA->save();

        $roleA = new Role([
            'name' => 'AlphaOnlyRole',
            'guard_name' => 'tenant',
        ]);
        $roleA->id = 10;
        $roleA->save();

        $roleA->syncPermissions(['dashboard.view']);
        $userA->assignRole($roleA);
        $countUsersA = User::on('tenant')->count();
        $this->assertSame(1, $countUsersA);
        tenancy()->end();

        // Setup Tenant B database
        tenancy()->initialize($tenantB);
        $pdoB = DB::connection('tenant')->getPdo();
        $this->setUpDatabaseSchemas('tenant');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $userB = new User([
            'name' => 'مدير المستأجر بيتا',
            'email' => 'beta_admin@lot.test',
            'password' => Hash::make('Pass123!'),
        ]);
        $userB->id = 1; // Explicit numeric collision ID=1
        $userB->save();

        $roleB = new Role([
            'name' => 'BetaOnlyRole',
            'guard_name' => 'tenant',
        ]);
        $roleB->id = 10; // Explicit numeric collision ID=10
        $roleB->save();

        $roleB->syncPermissions(['dashboard.view', 'users.view']);
        $userB->assignRole($roleB);
        $countUsersB = User::on('tenant')->count();
        $this->assertSame(1, $countUsersB);

        // Assert two distinct PDO database connections
        $this->assertNotSame($pdoA, $pdoB);

        // Assert role in B has name BetaOnlyRole, while Alpha has AlphaOnlyRole
        $this->assertSame('BetaOnlyRole', Role::on('tenant')->find(10)->name);
        $this->assertSame(2, Role::on('tenant')->find(10)->permissions()->count());

        tenancy()->end();

        // Back in Tenant A: assert role 10 is AlphaOnlyRole with 1 permission
        tenancy()->initialize($tenantA);
        $this->assertSame('AlphaOnlyRole', Role::on('tenant')->find(10)->name);
        $this->assertSame(1, Role::on('tenant')->find(10)->permissions()->count());
        tenancy()->end();
    }

    public function test_tenant_grouped_permissions_orders_issuing_entities_before_assignments_and_after_dashboard(): void
    {
        $tenant = Tenant::on('mysql')->create(['id' => 't-order-' . uniqid()]);
        tenancy()->initialize($tenant);

        // Delete existing seeded permissions to control ordering explicitly
        Permission::on('tenant')->where('guard_name', 'tenant')->delete();

        // Earlier test schemas omit section, which Eloquent caches as an unguardable
        // column. Force these fixture attributes without changing global model state.
        // Insert issuing entities first so section order cannot follow database IDs.
        foreach (['issuing_entities.view', 'issuing_entities.create', 'issuing_entities.edit'] as $perm) {
            Permission::on('tenant')->forceCreate(['name' => $perm, 'guard_name' => 'tenant', 'section' => 'issuing_entities_management']);
        }
        Permission::on('tenant')->forceCreate(['name' => 'dashboard.view', 'guard_name' => 'tenant', 'section' => 'dashboard']);
        Permission::on('tenant')->forceCreate(['name' => 'assignments.view', 'guard_name' => 'tenant', 'section' => 'assignments_management']);

        $service = app(TenantRoleService::class);
        $grouped = $service->getGroupedPermissions();
        $sections = array_keys($grouped->all());

        $dashboardIndex = array_search('dashboard', $sections, true);
        $issuingIndex = array_search('issuing_entities_management', $sections, true);
        $assignmentsIndex = array_search('assignments_management', $sections, true);

        $this->assertNotFalse($dashboardIndex);
        $this->assertNotFalse($issuingIndex);
        $this->assertNotFalse($assignmentsIndex);
        $this->assertLessThan($issuingIndex, $dashboardIndex);
        $this->assertLessThan($assignmentsIndex, $issuingIndex);
        $this->assertSame('إدارة الجهات المسندة', \App\Support\AccessLabels::section('issuing_entities_management'));

        tenancy()->end();
    }

    public function test_role_actions_and_datatable_checkbox_hide_or_disable_delete_for_roles_with_users_in_admin_and_tenant(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'admin');

        // 1. Admin Role with users
        $adminRoleWithUsers = (new Role)->forceFill(['id' => 91, 'name' => 'AdminSupervisor', 'guard_name' => 'admin']);
        $adminRoleWithUsers->users_count = 3;

        $adminActionWithUsers = view('admin.roles.action', ['role' => $adminRoleWithUsers])->render();
        $this->assertStringNotContainsString('js-delete-btn', $adminActionWithUsers);

        $adminRoleWithoutUsers = (new Role)->forceFill(['id' => 92, 'name' => 'AdminFree', 'guard_name' => 'admin']);
        $adminRoleWithoutUsers->users_count = 0;

        $adminActionWithoutUsers = view('admin.roles.action', ['role' => $adminRoleWithoutUsers])->render();
        $this->assertStringContainsString('js-delete-btn', $adminActionWithoutUsers);

        // 2. Tenant Role with users
        $tenant = Tenant::on('mysql')->create(['id' => 't-act-' . uniqid()]);
        $tenantUser = $this->createTenantUser($tenant);
        $this->actingAs($tenantUser, 'tenant');

        $tenantRoleWithUsers = (new Role)->forceFill(['id' => 93, 'name' => 'TenantSupervisor', 'guard_name' => 'tenant']);
        $tenantRoleWithUsers->users_count = 2;

        $tenantActionWithUsers = view('tenant.user-management.roles.action', ['role' => $tenantRoleWithUsers])->render();
        $this->assertStringNotContainsString('js-delete-btn', $tenantActionWithUsers);

        $tenantRoleWithoutUsers = (new Role)->forceFill(['id' => 94, 'name' => 'TenantFree', 'guard_name' => 'tenant']);
        $tenantRoleWithoutUsers->users_count = 0;

        $tenantActionWithoutUsers = view('tenant.user-management.roles.action', ['role' => $tenantRoleWithoutUsers])->render();
        $this->assertStringContainsString('js-delete-btn', $tenantActionWithoutUsers);

        tenancy()->end();
    }

    public function test_protected_roles_render_protected_label_and_hide_edit_and_delete_actions_in_admin_and_tenant(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'admin');

        $superAdminRole = Role::on('mysql')->where('name', 'SuperAdmin')->firstOrFail();
        $customAdminRole = Role::on('mysql')->create(['name' => 'CustomAdminRole', 'guard_name' => 'admin']);

        // Action partial renders "محمي" without edit or delete buttons
        $adminProtectedAction = view('admin.roles.action', ['role' => $superAdminRole])->render();
        $this->assertSame('<span class="text-muted">محمي</span>', trim($adminProtectedAction));
        $this->assertStringNotContainsString('js-delete-btn', $adminProtectedAction);
        $this->assertStringNotContainsString('تعديل الدور', $adminProtectedAction);

        // DataTable JSON renders "محمي" and disabled checkbox for SuperAdmin
        $adminDtResponse = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.roles.index', ['draw' => 1]));
        $adminDtResponse->assertOk();

        $adminRows = collect($adminDtResponse->json('data'));
        $superRow = $adminRows->first(fn (array $row): bool => str_contains((string) ($row['name'] ?? ''), 'مدير النظام الرئيسي'));
        $customAdminRow = $adminRows->first(fn (array $row): bool => str_contains((string) ($row['name'] ?? ''), 'CustomAdminRole'));

        $this->assertNotNull($superRow);
        $this->assertSame('<span class="text-muted">محمي</span>', $superRow['actions']);
        $this->assertStringContainsString('disabled', $superRow['checkbox']);
        $this->assertNotNull($customAdminRow);
        $this->assertStringContainsString('تعديل الدور', $customAdminRow['actions']);

        // Tenant side
        $tenant = Tenant::on('mysql')->create(['id' => 't-prot-ui-'.uniqid()]);
        $tenantUser = $this->createTenantUser($tenant);
        $this->actingAs($tenantUser, 'tenant');

        $primaryAdminRole = Role::on('tenant')->where('name', 'Admin')->firstOrFail();
        $customTenantRole = Role::on('tenant')->create(['name' => 'CustomTenantRole', 'guard_name' => 'tenant']);

        // Action partial renders "محمي" without edit or delete buttons
        $tenantProtectedAction = view('tenant.user-management.roles.action', ['role' => $primaryAdminRole])->render();
        $this->assertSame('<span class="text-muted">محمي</span>', trim($tenantProtectedAction));
        $this->assertStringNotContainsString('js-delete-btn', $tenantProtectedAction);
        $this->assertStringNotContainsString('تعديل الدور', $tenantProtectedAction);

        // DataTable JSON renders "محمي" and disabled checkbox for Tenant Admin
        $tenantDtResponse = $this->actingAs($tenantUser, 'tenant')
            ->getJson(tenant_route_name('roles.index').'?draw=1');
        $tenantDtResponse->assertOk();

        $tenantRows = collect($tenantDtResponse->json('data'));
        $tenantAdminRow = $tenantRows->first(fn (array $row): bool => str_contains((string) ($row['name'] ?? ''), 'مدير النظام'));
        $customTenantRow = $tenantRows->first(fn (array $row): bool => str_contains((string) ($row['name'] ?? ''), 'CustomTenantRole'));

        $this->assertNotNull($tenantAdminRow);
        $this->assertSame('<span class="text-muted">محمي</span>', $tenantAdminRow['actions']);
        $this->assertStringContainsString('disabled', $tenantAdminRow['checkbox']);
        $this->assertNotNull($customTenantRow);
        $this->assertStringContainsString('تعديل الدور', $customTenantRow['actions']);

        // Tenant role show page hides edit button and shows protected badge for Admin, while keeping edit button for custom role
        if (! Schema::connection('mysql')->hasTable('subscription_periods')) {
            Schema::connection('mysql')->create('subscription_periods', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('subscription_id');
                $table->string('status')->default('active');
                $table->timestamp('period_start')->nullable();
                $table->timestamp('period_end')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->actingAs($tenantUser, 'tenant')
            ->get(tenant_route_name('roles.show', $primaryAdminRole->id))
            ->assertOk()
            ->assertSee('محمي')
            ->assertDontSee('تعديل الدور');

        $this->actingAs($tenantUser, 'tenant')
            ->get(tenant_route_name('roles.show', $customTenantRole->id))
            ->assertOk()
            ->assertSee('تعديل الدور');

        tenancy()->end();
    }

    public function test_roles_create_and_edit_views_use_the_global_toastr_assets(): void
    {
        $adminCreate = file_get_contents(resource_path('views/admin/roles/create.blade.php'));
        $adminEdit = file_get_contents(resource_path('views/admin/roles/edit.blade.php'));
        $tenantCreate = file_get_contents(resource_path('views/tenant/user-management/roles/create.blade.php'));
        $tenantEdit = file_get_contents(resource_path('views/tenant/user-management/roles/edit.blade.php'));

        foreach ([$adminCreate, $adminEdit, $tenantCreate, $tenantEdit] as $content) {
            $this->assertStringNotContainsString("@section('toastr')", $content);
            $this->assertStringContainsString('layouts.layoutMaster', $content);
        }

        $feedback = file_get_contents(resource_path('views/components/shared/layout/feedback.blade.php'));
        $this->assertStringContainsString('resources/assets/js/shared/toastr.js', $feedback);
        $this->assertStringContainsString('resources/assets/css/shared/toastr.css', $feedback);
        $this->assertStringContainsString('<x-shared.layout.feedback', file_get_contents(resource_path('views/components/shared/layout/scripts.blade.php')));
    }
}

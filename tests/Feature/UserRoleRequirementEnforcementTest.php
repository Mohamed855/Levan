<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Tenants\Auth\RegisterTenantUserAction;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\AdminMangment\Users\UserService as AdminUserService;
use App\Services\Tenants\UserManagement\User\UserService as TenantUserService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class UserRoleRequirementEnforcementTest extends TestCase
{
    private AdminUser $superAdmin;

    private Role $adminRole;

    private Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'admin']);
        $this->managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'admin']);

        $this->superAdmin = AdminUser::where('email', 'superadmin@gmail.com')->first() ?? AdminUser::first();
        if ($this->superAdmin && ! $this->superAdmin->hasRole('SuperAdmin')) {
            $this->superAdmin->assignRole('SuperAdmin');
        }
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | SuperAdmin User Role Tests
    |--------------------------------------------------------------------------
    */

    public function test_superadmin_user_create_page_renders_with_roles_select(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('name="role_id"', false);
        $response->assertSee('اختر الدور');
    }

    public function test_superadmin_cannot_create_user_without_role(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'مستخدم بدون دور',
                'email' => 'no_role_' . random_int(1000, 9999) . '@lot.test',
                'phone' => '05' . random_int(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_superadmin_cannot_create_user_with_invalid_or_tenant_guard_role(): void
    {
        $tenantRole = Role::firstOrCreate(['name' => 'TenantRoleTest', 'guard_name' => 'tenant']);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'مستخدم دور خاطئ',
                'email' => 'wrong_role_' . random_int(1000, 9999) . '@lot.test',
                'phone' => '05' . random_int(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role_id' => $tenantRole->id,
            ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_superadmin_creates_user_with_role_successfully(): void
    {
        $email = 'admin_user_' . random_int(1000, 9999) . '@lot.test';
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'مشرف نظام جديد',
                'email' => $email,
                'phone' => '05' . random_int(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $createdUser = AdminUser::where('email', $email)->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasRole('Admin'));
        $this->assertSame(1, $createdUser->roles()->count());

        $createdUser->forceDelete();
    }

    public function test_superadmin_user_service_throws_when_role_id_is_missing(): void
    {
        $service = app(AdminUserService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('لا يمكن إنشاء مستخدم بدون دور.');

        $service->create([
            'name' => 'مستخدم بدون دور عبر الخدمة',
            'email' => 'service_no_role_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => 'Password123!',
        ]);
    }

    public function test_superadmin_user_edit_page_renders_with_roles_and_selected_current_role(): void
    {
        $user = AdminUser::create([
            'name' => 'مستخدم للتعديل',
            'email' => 'edit_user_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $user->syncRoles([$this->adminRole]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.users.edit', $user));

        $response->assertOk();
        $response->assertSee('name="role_id"', false);
        $response->assertSee('اختر الدور');

        $user->forceDelete();
    }

    public function test_superadmin_cannot_update_user_without_role(): void
    {
        $user = AdminUser::create([
            'name' => 'مستخدم تحديث بدون دور',
            'email' => 'update_no_role_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $user->syncRoles([$this->adminRole]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->put(route('admin.users.update', $user), [
                'name' => 'تحديث الاسم',
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response->assertSessionHasErrors('role_id');

        $user->forceDelete();
    }

    public function test_superadmin_updates_user_role_successfully(): void
    {
        $user = AdminUser::create([
            'name' => 'مستخدم تحديث الدور',
            'email' => 'update_role_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $user->syncRoles([$this->adminRole]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->put(route('admin.users.update', $user), [
                'name' => 'اسم معدل',
                'email' => $user->email,
                'phone' => $user->phone,
                'role_id' => $this->managerRole->id,
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue($user->hasRole('Manager'));
        $this->assertFalse($user->hasRole('Admin'));

        $user->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant User Role & Registration Tests
    |--------------------------------------------------------------------------
    */

    public function test_tenant_cannot_create_user_without_role(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);

        $tenantAdmin = User::first();
        if ($tenantAdmin && ! $tenantAdmin->hasRole('Admin')) {
            $tenantAdmin->assignRole('Admin');
        }

        $response = $this->actingAs($tenantAdmin, 'tenant')
            ->post('http://test.localhost/users', [
                'name' => 'مستخدم تينانت بدون دور',
                'email' => 'tenant_no_role_' . random_int(1000, 9999) . '@lot.test',
                'phone' => '05' . random_int(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_tenant_cannot_create_user_with_admin_guard_role(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);

        $tenantAdmin = User::first();
        if ($tenantAdmin && ! $tenantAdmin->hasRole('Admin')) {
            $tenantAdmin->assignRole('Admin');
        }

        $nonTenantRole = Role::create([
            'name' => 'admin_role_' . random_int(1000, 9999),
            'guard_name' => 'admin',
        ]);

        $response = $this->actingAs($tenantAdmin, 'tenant')
            ->post('http://test.localhost/users', [
                'name' => 'مستخدم دور خاطئ',
                'email' => 'tenant_wrong_role_' . random_int(1000, 9999) . '@lot.test',
                'phone' => '05' . random_int(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role_id' => $nonTenantRole->id,
            ]);

        $response->assertSessionHasErrors('role_id');

        $nonTenantRole->delete();
    }

    public function test_tenant_user_service_throws_when_role_id_is_missing(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);

        $service = app(TenantUserService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('لا يمكن إنشاء مستخدم بدون تحديد دور.');

        $service->createUser([
            'name' => 'مستخدم تينانت مباشر بدون دور',
            'email' => 'tenant_service_no_role_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => 'Password123!',
        ]);
    }

    public function test_tenant_cannot_update_user_without_role(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);

        $tenantAdmin = User::first();
        if ($tenantAdmin && ! $tenantAdmin->hasRole('Admin')) {
            $tenantAdmin->assignRole('Admin');
        }

        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'tenant']);

        $user = User::create([
            'name' => 'مستخدم تينانت للتحديث',
            'email' => 'tenant_update_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $user->syncRoles([$userRole]);

        $response = $this->actingAs($tenantAdmin, 'tenant')
            ->put("http://test.localhost/users/{$user->id}", [
                'name' => 'مستخدم تينانت معدل',
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response->assertSessionHasErrors('role_id');

        $user->forceDelete();
    }

    public function test_tenant_updates_user_role_successfully(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);

        $tenantAdmin = User::first();
        if ($tenantAdmin && ! $tenantAdmin->hasRole('Admin')) {
            $tenantAdmin->assignRole('Admin');
        }

        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'tenant']);
        $newRole = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'tenant']);

        $user = User::create([
            'name' => 'مستخدم تبديل الدور',
            'email' => 'tenant_switch_role_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $user->syncRoles([$userRole]);

        $response = $this->actingAs($tenantAdmin, 'tenant')
            ->put("http://test.localhost/users/{$user->id}", [
                'name' => 'مستخدم تم تعديل دوره',
                'email' => $user->email,
                'phone' => $user->phone,
                'role_id' => $newRole->id,
            ]);

        $response->assertRedirect('http://test.localhost/users');

        $user->refresh();
        $this->assertTrue($user->hasRole('Supervisor'));
        $this->assertFalse($user->hasRole('User'));

        $user->forceDelete();
        $newRole->delete();
    }

    public function test_tenant_registration_automatically_assigns_user_role(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);

        $registerAction = app(RegisterTenantUserAction::class);
        $registeredUser = $registerAction->execute([
            'name' => 'مسجل جديد في المنظومة',
            'email' => 'registered_user_' . random_int(1000, 9999) . '@lot.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => 'Password123!',
        ]);

        $this->assertNotNull($registeredUser);
        $this->assertTrue($registeredUser->hasRole('User'));
        $this->assertSame(1, $registeredUser->roles()->count());

        $registeredUser->forceDelete();
    }
}

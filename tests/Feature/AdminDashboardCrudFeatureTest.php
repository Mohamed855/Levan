<?php

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use App\Models\Admin\Tickets\TicketSection;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardCrudFeatureTest extends TestCase
{
    protected AdminUser $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->superAdmin = AdminUser::where('email', 'superadmin@gmail.com')->first();
        $this->actingAs($this->superAdmin, 'admin');
    }

    protected function adminUrl(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path ? 'http://localhost/admin/'.$path : 'http://localhost/admin';
    }

    /**
     * Every registered Super Admin route must point to an existing controller method.
     */
    public function test_all_super_admin_routes_target_existing_controller_methods(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin'));

        $this->assertNotEmpty($routes, 'No Super Admin routes are registered.');

        foreach ($routes as $route) {
            $action = $route->getActionName();

            if ($action === 'Closure') {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            $this->assertTrue(
                class_exists($controller) && method_exists($controller, $method),
                "Route [{$route->getName()}] targets unavailable action [{$action}]."
            );
        }
    }

    /**
     * Test 1: Admin Dashboard Index renders successfully with live statistics
     */
    public function test_admin_dashboard_renders_successfully(): void
    {
        $response = $this->get($this->adminUrl('/'));
        $response->assertStatus(200);
        $response->assertSee('لوحة القيادة');
    }

    public function test_client_wizard_returns_a_validation_error_for_an_existing_company_name(): void
    {
        $client = Tenant::query()->firstOrFail();

        $this->postJson($this->adminUrl('wizard-drafts/validate-step'), [
            'target_path' => '/admin/clients',
            'fields' => ['company_name'],
            'company_name' => $client->company_name,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name']);
    }

    /**
     * Test 2: Admin Users CRUD, Profile, Unique Checks & Archive Lifecycle
     */
    public function test_admin_users_crud_and_archive_lifecycle(): void
    {
        // 1. Index
        $this->get($this->adminUrl('users'))->assertStatus(200);

        // 2. Create view
        $this->get($this->adminUrl('users/create'))->assertStatus(200);

        // 3. Unique checks via AJAX
        $this->postJson($this->adminUrl('users/check-email-unique'), ['email' => 'superadmin@gmail.com'])
            ->assertStatus(200)
            ->assertJson(['available' => false]);

        $uniqueEmail = 'admin'.rand(1000, 9999).'@gmail.com';
        $this->postJson($this->adminUrl('users/check-email-unique'), ['email' => $uniqueEmail])
            ->assertStatus(200)
            ->assertJson(['available' => true]);

        $this->postJson($this->adminUrl('users/check-phone-unique'), ['phone' => '0599999999'])
            ->assertStatus(200);

        $this->postJson($this->adminUrl('users/check-name-unique'), ['name' => 'مشرف جديد'])
            ->assertStatus(200);

        // 4. Store
        $userName = 'مشرف اختباري';
        $phone = '05'.rand(10000000, 99999999);
        $storeResponse = $this->post($this->adminUrl('users'), [
            'name' => $userName,
            'email' => $uniqueEmail,
            'password' => '123456789',
            'password_confirmation' => '123456789',
            'phone' => $phone,
        ]);
        $storeResponse->assertStatus(302);

        $user = AdminUser::where('email', $uniqueEmail)->first();
        $this->assertNotNull($user, 'Admin user was not created in database.');

        // 5. Profile view
        $this->get($this->adminUrl("users/profile/{$user->id}"))->assertStatus(200);

        // 6. Edit view
        $this->get($this->adminUrl("users/{$user->id}/edit"))->assertStatus(200);

        // 7. Update
        $updatedName = 'مشرف اختباري معدل';
        $updateResponse = $this->put($this->adminUrl("users/{$user->id}"), [
            'name' => $updatedName,
            'email' => $uniqueEmail,
            'phone' => $phone,
        ]);
        $updateResponse->assertStatus(302);
        $user->refresh();
        $this->assertEquals($updatedName, $user->name);

        // 8. Toggle Status
        $toggleResponse = $this->patch($this->adminUrl("users/{$user->id}/toggle-status"));
        $toggleResponse->assertStatus(200);

        // 9. Soft Delete (Destroy)
        $destroyResponse = $this->delete($this->adminUrl("users/{$user->id}"));
        $destroyResponse->assertStatus(302);
        $user->refresh();
        $this->assertNotNull($user->deleted_at, 'Admin user was not soft-deleted.');

        // 10. Archive Index
        $this->get($this->adminUrl('archives'))->assertStatus(200);

        // 11. Restore
        $restoreResponse = $this->patch($this->adminUrl("archives/restore/{$user->id}"));
        $restoreResponse->assertStatus(302);
        $user->refresh();
        $this->assertNull($user->deleted_at, 'Admin user was not restored.');

        // 12. Force Delete
        $this->delete($this->adminUrl("users/{$user->id}"));
        $forceDeleteResponse = $this->delete($this->adminUrl("archives/force-delete/{$user->id}"));
        $forceDeleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('admin_users', ['id' => $user->id]);
    }

    /**
     * Test 2b: Admin Users Super Admin protection on all destructive and status operations
     */
    public function test_admin_users_super_admin_protection_on_all_operations(): void
    {
        // 1. Cannot toggle status of Super Admin
        $this->patchJson($this->adminUrl("users/{$this->superAdmin->id}/toggle-status"))
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'لا يمكن تغيير حالة السوبر أدمن',
            ]);

        // 2. Cannot soft-delete Super Admin
        $destroyResponse = $this->delete($this->adminUrl("users/{$this->superAdmin->id}"));
        $destroyResponse->assertStatus(302);
        $this->assertNull($this->superAdmin->fresh()->deleted_at);

        // 3. Cannot bulk-delete Super Admin
        $this->postJson($this->adminUrl('users/bulk-delete'), ['ids' => [$this->superAdmin->id]])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'لا يمكن حذف السوبر أدمن',
            ]);

        // 4. Cannot restore or force-delete Super Admin via archive
        $tempSuperAdmin = AdminUser::create([
            'name' => 'مشرف عام تجريبي',
            'email' => 'super_temp_'.uniqid().'@gmail.com',
            'password' => bcrypt('password123'),
            'phone' => '05'.rand(10000000, 99999999),
            'status' => 'active',
            'is_super_admin' => 1,
        ]);
        $superAdminRole = Role::where('name', 'SuperAdmin')->where('guard_name', 'admin')->first();
        if ($superAdminRole) {
            $tempSuperAdmin->assignRole($superAdminRole);
        }
        $tempSuperAdmin->delete();

        // Attempt restore on SuperAdmin
        $this->patch($this->adminUrl("archives/restore/{$tempSuperAdmin->id}"))
            ->assertStatus(302);
        $this->assertNotNull($tempSuperAdmin->fresh()->deleted_at);

        // Attempt force-delete on SuperAdmin
        $this->delete($this->adminUrl("archives/force-delete/{$tempSuperAdmin->id}"))
            ->assertStatus(302);
        $this->assertDatabaseHas('admin_users', ['id' => $tempSuperAdmin->id]);

        // Clean up
        $tempSuperAdmin->removeRole('SuperAdmin');
        $tempSuperAdmin->forceDelete();
    }

    /**
     * Test 2c: Admin Users bulk delete and stored-image cleanup
     */
    public function test_admin_users_bulk_delete_and_stored_image_cleanup(): void
    {
        Storage::fake('public');

        $imagePath1 = 'admin_users/avatar_test1.png';
        $imagePath2 = 'admin_users/avatar_test2.png';
        Storage::disk('public')->put($imagePath1, 'fake content 1');
        Storage::disk('public')->put($imagePath2, 'fake content 2');

        $user1 = AdminUser::create([
            'name' => 'مستخدم صورة 1',
            'email' => 'img1_'.uniqid().'@gmail.com',
            'password' => bcrypt('password123'),
            'phone' => '05'.rand(10000000, 99999999),
            'status' => 'active',
            'image' => $imagePath1,
        ]);

        $user2 = AdminUser::create([
            'name' => 'مستخدم صورة 2',
            'email' => 'img2_'.uniqid().'@gmail.com',
            'password' => bcrypt('password123'),
            'phone' => '05'.rand(10000000, 99999999),
            'status' => 'active',
            'image' => $imagePath2,
        ]);

        // Bulk delete both users
        $response = $this->postJson($this->adminUrl('users/bulk-delete'), [
            'ids' => [$user1->id, $user2->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotNull($user1->fresh()->deleted_at);
        $this->assertNotNull($user2->fresh()->deleted_at);
        Storage::disk('public')->assertMissing($imagePath1);
        Storage::disk('public')->assertMissing($imagePath2);

        // Force delete image cleanup
        $imagePath3 = 'admin_users/avatar_test3.png';
        Storage::disk('public')->put($imagePath3, 'fake content 3');

        $user3 = AdminUser::create([
            'name' => 'مستخدم صورة 3',
            'email' => 'img3_'.uniqid().'@gmail.com',
            'password' => bcrypt('password123'),
            'phone' => '05'.rand(10000000, 99999999),
            'status' => 'active',
            'image' => $imagePath3,
        ]);
        $user3->delete();

        $this->delete($this->adminUrl("archives/force-delete/{$user3->id}"))
            ->assertStatus(302);

        $this->assertDatabaseMissing('admin_users', ['id' => $user3->id]);
        Storage::disk('public')->assertMissing($imagePath3);
    }

    /**
     * Test 2d: Admin Users uniqueness endpoints and current user ignore
     */
    public function test_admin_users_uniqueness_endpoints_and_self_ignore(): void
    {
        $uniqueEmail = 'target_'.uniqid().'@gmail.com';
        $uniquePhone = '05'.rand(10000000, 99999999);
        $uniqueName = 'مشرف فريد '.rand(1000, 9999);

        $targetUser = AdminUser::create([
            'name' => $uniqueName,
            'email' => $uniqueEmail,
            'password' => bcrypt('password123'),
            'phone' => $uniquePhone,
            'status' => 'active',
        ]);

        // Email uniqueness
        $this->postJson($this->adminUrl('users/check-email-unique'), [
            'email' => $uniqueEmail,
        ])->assertStatus(200)->assertJson(['available' => false]);

        $this->postJson($this->adminUrl('users/check-email-unique'), [
            'email' => $uniqueEmail,
            'user_id' => $targetUser->id,
        ])->assertStatus(200)->assertJson(['available' => true]);

        $this->postJson($this->adminUrl('users/check-email-unique'), [
            'email' => $uniqueEmail,
            'user_id' => 999999,
        ])->assertStatus(200)->assertJson(['available' => false]);

        // Phone uniqueness
        $this->postJson($this->adminUrl('users/check-phone-unique'), [
            'phone' => $uniquePhone,
        ])->assertStatus(200)->assertJson(['available' => false]);

        $this->postJson($this->adminUrl('users/check-phone-unique'), [
            'phone' => $uniquePhone,
            'user_id' => $targetUser->id,
        ])->assertStatus(200)->assertJson(['available' => true]);

        // Name uniqueness
        $this->postJson($this->adminUrl('users/check-name-unique'), [
            'name' => $uniqueName,
        ])->assertStatus(200)->assertJson(['available' => false]);

        $this->postJson($this->adminUrl('users/check-name-unique'), [
            'name' => $uniqueName,
            'user_id' => $targetUser->id,
        ])->assertStatus(200)->assertJson(['available' => true]);

        // Validation rejection
        $this->postJson($this->adminUrl('users/check-email-unique'), [
            'email' => 'not-an-email',
        ])->assertStatus(422);

        $this->postJson($this->adminUrl('users/check-phone-unique'), [
            'phone' => 'invalid-phone-string-with-letters',
        ])->assertStatus(422);

        $targetUser->forceDelete();
    }

    /**
     * Test 2e: Admin Users authorization for bulk delete, archive operations, and uniqueness endpoints
     */
    public function test_admin_users_authorization_for_bulk_delete_archives_and_uniqueness(): void
    {
        $restrictedUser = AdminUser::create([
            'name' => 'مشرف مقيد',
            'email' => 'restricted_'.uniqid().'@gmail.com',
            'password' => bcrypt('password123'),
            'phone' => '05'.rand(10000000, 99999999),
            'status' => 'active',
        ]);

        $this->actingAs($restrictedUser, 'admin');

        // 1. Bulk delete forbidden
        $this->postJson($this->adminUrl('users/bulk-delete'), ['ids' => [1]])
            ->assertStatus(403);

        // 2. Uniqueness endpoints forbidden
        $this->postJson($this->adminUrl('users/check-email-unique'), ['email' => 'test@test.com'])
            ->assertStatus(403);
        $this->postJson($this->adminUrl('users/check-name-unique'), ['name' => 'مشرف'])
            ->assertStatus(403);
        $this->postJson($this->adminUrl('users/check-phone-unique'), ['phone' => '0500000000'])
            ->assertStatus(403);

        // 3. Archive restore and force-delete forbidden
        $this->patch($this->adminUrl('archives/restore/1'))
            ->assertStatus(403);
        $this->delete($this->adminUrl('archives/force-delete/1'))
            ->assertStatus(403);

        // Restore SuperAdmin session
        $this->actingAs($this->superAdmin, 'admin');
        $restrictedUser->forceDelete();
    }

    /**
     * Test 2f: Admin Users active and archive DataTables filter by status and role
     */
    public function test_admin_users_datatables_filter_by_status_and_role(): void
    {
        // 1. Users DataTable with status and role filters
        $usersResponse = $this->getJson($this->adminUrl('users?status=active&role=SuperAdmin'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $usersResponse->assertStatus(200);
        $usersResponse->assertJsonStructure(['data', 'draw', 'recordsTotal', 'recordsFiltered']);

        // 2. Users DataTable with inactive filter
        $inactiveResponse = $this->getJson($this->adminUrl('users?status=inactive'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $inactiveResponse->assertStatus(200);

        // 3. Archives DataTable with status and role filters
        $archiveResponse = $this->getJson($this->adminUrl('archives?status=active&role=SuperAdmin'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $archiveResponse->assertStatus(200);
        $archiveResponse->assertJsonStructure(['data', 'draw', 'recordsTotal', 'recordsFiltered']);
    }

    /**
     * Test 3: Admin Roles CRUD Lifecycle & User Role Assignments
     */
    public function test_admin_roles_crud_lifecycle(): void
    {
        // 1. Index
        $this->get($this->adminUrl('roles'))->assertStatus(200);

        // 2. Create view
        $this->get($this->adminUrl('roles/create'))->assertStatus(200);

        // 3. Store
        $suffix = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
        $roleName = 'محرر النظام '.$suffix;
        $storeResponse = $this->post($this->adminUrl('roles'), [
            'name' => $roleName,
            'select_all' => 1,
        ]);
        $storeResponse->assertStatus(302);

        $role = Role::where('name', $roleName)->where('guard_name', 'admin')->first();
        $this->assertNotNull($role, 'Role was not created in database.');

        // 4. Edit view
        $this->get($this->adminUrl("roles/{$role->id}/edit"))->assertStatus(200);

        // 5. Update
        $updatedRoleName = $roleName.' معدل';
        $updateResponse = $this->put($this->adminUrl("roles/{$role->id}"), [
            'name' => $updatedRoleName,
            'select_all' => 1,
        ]);
        $updateResponse->assertStatus(302);
        $role->refresh();
        $this->assertEquals($updatedRoleName, $role->name);

        // 6. User Roles Management endpoints
        $this->get($this->adminUrl("roles/get-user-roles/{$this->superAdmin->id}"))
            ->assertStatus(200);

        $superAdminRole = Role::where('name', 'SuperAdmin')->where('guard_name', 'admin')->first();
        $this->postJson($this->adminUrl("roles/update-user-roles/{$this->superAdmin->id}"), [
            'role' => $superAdminRole->id,
        ])->assertStatus(200);

        // 7. Destroy
        $destroyResponse = $this->delete($this->adminUrl("roles/{$role->id}"));
        $destroyResponse->assertStatus(302);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /**
     * Test 4: General Settings, Banks & Domains CRUD Lifecycle
     */
    public function test_admin_settings_banks_and_domains_crud_lifecycle(): void
    {
        Storage::fake('public');

        // === A. General Settings ===
        $this->get($this->adminUrl('settings'))->assertStatus(200);
        $updateSettingsResponse = $this->put($this->adminUrl('settings'), [
            'office_name' => 'شركة لوت للحلول العقارية والمزادات',
            'logo_text' => 'لوت LOT',
            'office_address' => 'الرياض - طريق الملك فهد',
            'commercial_register' => '1010123456',
            'tax_number' => '300123456700003',
            'phone_number' => '0112345678',
            'email' => 'info@lot.sa',
        ]);
        $updateSettingsResponse->assertStatus(302);

        $settings = Settings::first();
        $this->assertEquals('شركة لوت للحلول العقارية والمزادات', $settings->office_name);

        // === B. Banks Management ===
        $this->get($this->adminUrl('settings/banks'))->assertStatus(200);
        $this->get($this->adminUrl('settings/banks/create'))->assertStatus(200);

        $fakeIcon = UploadedFile::fake()->image('bank.png', 100, 100);
        $bankName = 'بنك الإنماء '.rand(100, 999);
        $storeBankResponse = $this->post($this->adminUrl('settings/banks'), [
            'bank_name' => $bankName,
            'icon' => $fakeIcon,
            'account_name' => 'حساب لوت',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
        ]);
        $storeBankResponse->assertStatus(302);

        $bank = Bank::where('bank_name', $bankName)->first();
        $this->assertNotNull($bank, 'Bank was not created in database.');

        $this->get($this->adminUrl("settings/banks/{$bank->id}/edit"))->assertStatus(200);

        $updateBankResponse = $this->put($this->adminUrl("settings/banks/{$bank->id}"), [
            'bank_name' => $bankName.' معدل',
            'account_name' => 'حساب لوت التجاري',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
        ]);
        $updateBankResponse->assertStatus(302);

        $this->delete($this->adminUrl("settings/banks/{$bank->id}"))->assertStatus(302);
        $this->assertDatabaseMissing('banks', ['id' => $bank->id]);

        // === C. Control Domains Management ===
        $this->get($this->adminUrl('settings/domains'))->assertStatus(200);
        $this->get($this->adminUrl('settings/domains/create'))->assertStatus(200);

        $ext = '.levan'.rand(10, 99);
        $storeDomainResponse = $this->post($this->adminUrl('settings/domains'), [
            'domain_extension' => $ext,
            'price' => 150.00,
        ]);
        $storeDomainResponse->assertStatus(302);

        $controlDomain = ControlDomain::where('domain_extension', $ext)->first();
        $this->assertNotNull($controlDomain, 'ControlDomain was not created.');

        $this->get($this->adminUrl("settings/domains/{$controlDomain->id}/edit"))->assertStatus(200);

        $this->put($this->adminUrl("settings/domains/{$controlDomain->id}"), [
            'domain_extension' => $ext,
            'price' => 180.00,
        ])->assertStatus(302);

        $this->delete($this->adminUrl("settings/domains/{$controlDomain->id}"))->assertStatus(302);
        $this->assertDatabaseMissing('control_domains', ['id' => $controlDomain->id]);
    }

    /**
     * Test 5: Plans Management CRUD & Archive Lifecycle
     */
    public function test_admin_plans_crud_and_archive_lifecycle(): void
    {
        // 1. Index
        $this->get($this->adminUrl('plans'))->assertStatus(200);

        // 2. Create view
        $this->get($this->adminUrl('plans/create'))->assertStatus(200);

        // 3. Store
        $planName = 'باقة المزادات المميزة '.rand(100, 999);
        $storeResponse = $this->post($this->adminUrl('plans'), [
            'name' => $planName,
            'code' => 'AUCTION_'.rand(10000, 99999),
            'plan_type' => 'professional',
            'color' => 'purple',
            'is_active' => 1,
            'price' => 12000,
            'extra_user_license_price' => 500,
            'yearly_renewal_price' => 10000,
            'included_users' => 5,
            'limits_mode' => 'limited',
            'properties_limit' => 50,
            'storage_quota_gb' => 10,
            'active_auctions_limit' => 10,
            'subscription_billing_cycle' => 'monthly',
            'description' => 'باقة مخصصة لإدارة المزادات العقارية',
            'discount_percentage' => 10,
            'domain_management_enabled' => 1,
            'features' => [
                'إدارة المزادات والمحافظ',
                'بوابة الدفع الإلكتروني',
                'تقارير وإحصائيات متقدمة',
            ],
        ]);
        $storeResponse->assertStatus(302);

        $plan = Plan::where('name', $planName)->first();
        $this->assertNotNull($plan, 'Plan was not created in database.');

        // 3.5 Show & Details view
        $showResponse = $this->get($this->adminUrl("plans/{$plan->id}"));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($plan->name);
        $showResponse->assertSee('تفاصيل الباقة');
        $showResponse->assertSee('ميزات الباقة');
        $showResponse->assertSee('الاشتراكات والشركات');

        $detailsResponse = $this->get($this->adminUrl("plans/details/{$plan->id}"));
        $detailsResponse->assertStatus(200);
        $detailsResponse->assertSee($plan->name);

        // 4. Edit view (including when features is null)
        $this->get($this->adminUrl("plans/{$plan->id}/edit"))->assertStatus(200);
        $plan->update(['features' => null]);
        $this->get($this->adminUrl("plans/{$plan->id}/edit"))->assertStatus(200);

        // 5. Update
        $updatedPlanName = $planName.' معدلة';
        $updateResponse = $this->put($this->adminUrl("plans/{$plan->id}"), [
            'name' => $updatedPlanName,
            'code' => $plan->code,
            'plan_type' => 'professional',
            'color' => 'purple',
            'is_active' => 1,
            'price' => 15000,
            'extra_user_license_price' => 600,
            'yearly_renewal_price' => 12000,
            'included_users' => 10,
            'limits_mode' => 'limited',
            'properties_limit' => 50,
            'storage_quota_gb' => 10,
            'active_auctions_limit' => 10,
            'subscription_billing_cycle' => 'monthly',
            'domain_management_enabled' => 1,
            'features' => ['ميزة محدثة'],
        ]);
        $updateResponse->assertStatus(302);
        $plan->refresh();
        $this->assertEquals($updatedPlanName, $plan->name);

        // 6. Soft Delete
        $this->delete($this->adminUrl("plans/{$plan->id}"))->assertStatus(302);
        $plan->refresh();
        $this->assertNotNull($plan->deleted_at);

        // 7. Archive Index
        $this->get($this->adminUrl('archives/plans'))->assertStatus(200);

        // 8. Restore
        $this->patch($this->adminUrl("archives/plans/restore/{$plan->id}"))->assertStatus(302);
        $plan->refresh();
        $this->assertNull($plan->deleted_at);

        // 9. Force Delete
        $this->delete($this->adminUrl("plans/{$plan->id}"));
        $this->delete($this->adminUrl("archives/plans/force-delete/{$plan->id}"))->assertStatus(302);
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    /**
     * Test 6: Plan actions, mass deletion, archive restore, and company wizard validation.
     */
    public function test_admin_plan_actions_mass_deletion_and_company_wizard_validation(): void
    {
        $planName = 'باقة حذف جماعي '.rand(1000, 9999);
        $this->post($this->adminUrl('plans'), [
            'name' => $planName,
            'code' => 'DELETE_'.rand(10000, 99999),
            'plan_type' => 'basic',
            'color' => 'blue',
            'is_active' => 1,
            'price' => 1000,
            'extra_user_license_price' => 100,
            'yearly_renewal_price' => 900,
            'included_users' => 2,
            'limits_mode' => 'limited',
            'subscription_billing_cycle' => 'monthly',
            'properties_limit' => 10,
            'storage_quota_gb' => 5,
            'active_auctions_limit' => 5,
            'domain_management_enabled' => 1,
            'features' => ['اختبار الحذف الجماعي'],
        ])->assertStatus(302);

        $plan = Plan::where('name', $planName)->firstOrFail();

        $this->patchJson($this->adminUrl("plans/{$plan->id}/toggle-active"))
            ->assertOk()
            ->assertJson(['success' => true, 'is_active' => false]);

        $this->postJson($this->adminUrl('plans/update-sort-order'), [
            'order' => [['id' => $plan->id, 'sort_order' => 99]],
        ])->assertOk()
            ->assertJson(['success' => true]);

        $this->postJson($this->adminUrl('mass-delete'), [
            'model' => 'Plan',
            'ids' => [$plan->id],
        ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('plans', ['id' => $plan->id]);

        $this->patch($this->adminUrl("archives/plans/restore/{$plan->id}"))->assertStatus(302);
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'deleted_at' => null]);

        $this->delete($this->adminUrl("plans/{$plan->id}"))->assertStatus(302);
        $this->delete($this->adminUrl("archives/plans/force-delete/{$plan->id}"))->assertStatus(302);
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);

        $city = City::query()->firstOrFail();
        $stepUrl = $this->adminUrl('wizard-drafts/validate-step');

        $this->postJson($stepUrl, [
            'target_path' => '/admin/companies',
            'fields' => ['company_name'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name']);

        $this->postJson($stepUrl, [
            'target_path' => '/admin/companies',
            'fields' => ['company_name', 'commercial_number', 'tax_number', 'city_id', 'address'],
            'company_name' => 'شركة اختبار خطوات '.rand(1000, 9999),
            'commercial_number' => (string) rand(1000000000, 9999999999),
            'tax_number' => '3'.str_pad((string) rand(10000000000000, 99999999999999), 14, '0', STR_PAD_LEFT),
            'city_id' => $city->id,
            'address' => 'عنوان اختبار التحقق من خطوات النموذج',
        ])->assertOk()
            ->assertJson(['success' => true]);

        $testPlan = Plan::query()->firstOrFail();
        $this->postJson($stepUrl, [
            'target_path' => "/admin/plans/{$testPlan->id}",
            'target_method' => 'PUT',
            '_method' => 'PUT',
            'fields' => ['name_ar', 'price'],
            'name_ar' => $testPlan->name_ar,
            'price' => $testPlan->price,
        ])->assertOk()->assertJson(['success' => true]);

        $this->putJson($stepUrl, [
            'target_path' => "/admin/plans/{$testPlan->id}",
            'target_method' => 'PUT',
            'fields' => ['name_ar', 'price'],
            'name_ar' => $testPlan->name_ar,
            'price' => $testPlan->price,
        ])->assertOk()->assertJson(['success' => true]);
    }

    /**
     * Test 7: Subscription approval/rejection, bill actions, and ticket actions use real records.
     */
    public function test_admin_subscription_bill_and_ticket_actions_with_real_records(): void
    {
        $tenantId = 'dummy_admin_'.uniqid();
        $tenant = Tenant::find('test') ?? Tenant::first() ?? Tenant::create([
            'id' => $tenantId,
            'db_database' => $tenantId,
            'db_username' => 'root',
            'db_password' => '',
            'company_name' => 'شركة اختبار أدمن',
            'commercial_number' => (string) rand(1000000000, 9999999999),
            'tax_number' => '300000000000003',
        ]);
        $plan = Plan::query()->firstOrFail();

        $createSubscription = function () use ($tenant, $plan): Subscription {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'status' => SubscriptionStatus::PENDING,
                'price' => $plan->price,
                'total_users' => max(1, $plan->included_users),
                'used_users' => 0,
                'auto_renew' => false,
            ]);

            SubscriptionPeriod::create([
                'subscription_id' => $subscription->id,
                'period_start' => now(),
                'period_end' => now()->addMonth(),
                'plan_id_snapshot' => $plan->id,
                'price_snapshot' => $plan->price,
                'total_users_snapshot' => max(1, $plan->included_users),
                'used_users_snapshot' => 0,
                'status' => SubscriptionPeriodStatus::PENDING_PAYMENT,
                'is_current' => true,
            ]);

            return $subscription;
        };

        $acceptedSubscription = $createSubscription();
        $this->postJson($this->adminUrl("subscription-manage/{$acceptedSubscription->id}/accept"))
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::ACTIVE, $acceptedSubscription->fresh()->status);

        $rejectedSubscription = $createSubscription();
        $this->postJson($this->adminUrl("subscription-manage/{$rejectedSubscription->id}/reject"), [
            'reject_reason' => 'سبب رفض اختبار',
        ])->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::CANCELLED, $rejectedSubscription->fresh()->status);

        $massAcceptedSubscription = $createSubscription();
        $this->postJson($this->adminUrl('subscription-manage/mass-accept'), [
            'ids' => [$massAcceptedSubscription->id],
        ])->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::ACTIVE, $massAcceptedSubscription->fresh()->status);

        $massRejectedSubscription = $createSubscription();
        $this->postJson($this->adminUrl('subscription-manage/mass-reject'), [
            'ids' => [$massRejectedSubscription->id],
            'reject_reason' => 'رفض جماعي للاختبار',
        ])->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame(SubscriptionStatus::CANCELLED, $massRejectedSubscription->fresh()->status);

        $rejectedBill = Bill::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $acceptedSubscription->id,
            'amount' => 100,
            'original_amount' => 100,
            'type' => BillType::NEW_SUBSCRIPTION,
            'status' => BillStatus::PENDING_VERIFICATION,
        ]);
        $this->post($this->adminUrl("bills/{$rejectedBill->id}/reject"), [
            'reject_reason' => 'إيصال اختبار غير صالح',
        ])->assertStatus(302);
        $this->assertSame(BillStatus::UNPAID, $rejectedBill->fresh()->status);

        $reactivatedBill = Bill::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $acceptedSubscription->id,
            'amount' => 100,
            'original_amount' => 100,
            'type' => BillType::NEW_SUBSCRIPTION,
            'status' => BillStatus::CANCELLED,
        ]);
        $this->post($this->adminUrl("bills/{$reactivatedBill->id}/reactivate"))->assertStatus(302);
        $this->assertSame(BillStatus::UNPAID, $reactivatedBill->fresh()->status);

        $verifiedBill = Bill::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $acceptedSubscription->id,
            'amount' => 100,
            'original_amount' => 100,
            'type' => BillType::NEW_SUBSCRIPTION,
            'status' => BillStatus::PENDING_VERIFICATION,
        ]);
        $this->post($this->adminUrl("bills/{$verifiedBill->id}/verify"))->assertStatus(302);
        $this->assertSame(BillStatus::PAID, $verifiedBill->fresh()->status);

        $section = TicketSection::query()->firstOrCreate(
            ['name' => 'قسم اختبار الدعم'],
            ['is_active' => true],
        );
        $ticket = Ticket::create([
            'ticket_number' => 'TICKET-TEST-'.uniqid(),
            'tenant_id' => $tenant->id,
            'ticketing_sections_id' => $section->id,
            'title' => 'تذكرة اختبار Super Admin',
            'description' => 'تذكرة لاختبار الرد وتغيير الحالة.',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->post($this->adminUrl("tickets/{$ticket->ticket_number}/reply"), [
            'message' => 'رد اختبار من Super Admin',
        ])->assertStatus(302);
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'رد اختبار من Super Admin',
        ], 'mysql');
        $this->assertSame('in_progress', $ticket->fresh()->status);

        $this->post($this->adminUrl("tickets/{$ticket->ticket_number}/status"), [
            'status' => 'resolved',
        ])->assertStatus(302);
        $this->assertSame('resolved', $ticket->fresh()->status);

        $settings = Settings::firstOrFail();
        $originalTicketServiceStatus = $settings->tickets_enabled;
        $this->post($this->adminUrl('tickets/toggle-service'), [
            'tickets_enabled' => ! $originalTicketServiceStatus,
        ])->assertStatus(302);
        $this->assertSame(! $originalTicketServiceStatus, (bool) $settings->fresh()->tickets_enabled);
        $settings->update(['tickets_enabled' => $originalTicketServiceStatus]);

        TicketReply::where('ticket_id', $ticket->id)->delete();
        $ticket->delete();
        Bill::whereKey([$rejectedBill->id, $reactivatedBill->id, $verifiedBill->id])->forceDelete();
        Subscription::whereKey([
            $acceptedSubscription->id,
            $rejectedSubscription->id,
            $massAcceptedSubscription->id,
            $massRejectedSubscription->id,
        ])->forceDelete();
    }

    /**
     * Test 8: Company creation provisions a local tenant database and supports update and archive lifecycle.
     */
    public function test_admin_company_creation_update_and_archive_lifecycle(): void
    {
        $plan = Plan::query()
            ->where('is_active', true)
            ->where('subscription_duration_type', 'limited')
            ->firstOrFail();
        $city = City::query()->firstOrFail();
        $commercialNumber = (string) rand(1000000000, 9999999999);
        $tenant = null;
        $tenantDatabase = null;
        $startDate = now()->toDateString();
        $expectedEndDate = $plan->subscription_billing_cycle === 'monthly'
            ? now()->addMonth()->toDateString()
            : now()->addYear()->toDateString();
        $companyPayload = [
            'company_name' => 'شركة اختبار إنشاء محلي '.rand(1000, 9999),
            'commercial_number' => $commercialNumber,
            'tax_number' => '3'.str_pad((string) rand(10000000000000, 99999999999999), 14, '0', STR_PAD_LEFT),
            'city_id' => $city->id,
            'address' => 'عنوان اختبار إنشاء شركة محليًا',
            'name' => 'مدير شركة الاختبار',
            'phone' => '05'.rand(10000000, 99999999),
            'email' => 'company-'.uniqid().'@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan_id' => $plan->id,
            'start_date' => $startDate,
            'total_users' => max(1, $plan->included_users),
            'payment_type' => 'paid',
            'payment_method' => 'cash',
            'has_discount' => 'no',
            'activate_immediately' => 1,
        ];

        try {
            $invalidPayload = $companyPayload;
            $invalidPayload['end_date'] = now()->subDays(2)->toDateString();
            $this->post($this->adminUrl('companies'), $invalidPayload)
                ->assertSessionHasErrors('end_date');

            $companyPayload['end_date'] = $expectedEndDate;
            $this->post($this->adminUrl('companies'), $companyPayload)->assertRedirect($this->adminUrl('companies'));

            $tenant = Tenant::where('commercial_number', $commercialNumber)->firstOrFail();
            $tenantDatabase = $tenant->db_database;
            $subscription = Subscription::where('tenant_id', $tenant->id)->firstOrFail();
            $this->assertSame($expectedEndDate, Carbon::parse($subscription->ends_at)->toDateString());

            $tableResponse = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*',
            ])->get($this->adminUrl('companies?draw=1&start=0&length=50&search[value]='.urlencode($tenant->commercial_number)));
            $tableResponse->assertOk()->assertJsonPath('draw', 1);
            $this->assertTrue(
                collect($tableResponse->json('data'))->contains('id', $tenant->id),
                'The newly created company was not returned by the companies DataTable.',
            );
            $this->withoutHeader('X-Requested-With')->withoutHeader('Accept');

            $this->put($this->adminUrl("companies/{$tenant->id}"), [
                'company_name' => $tenant->company_name,
                'commercial_number' => $tenant->commercial_number,
                'tax_number' => $tenant->tax_number,
                'city_id' => $city->id,
                'address' => 'عنوان محدث لاختبار الشركة',
            ])->assertRedirect($this->adminUrl('companies'));
            $this->assertSame('عنوان محدث لاختبار الشركة', $tenant->fresh()->address);

            // Archive keeps subscription history attached to the archived company.
            $this->delete($this->adminUrl("companies/{$tenant->id}"))->assertRedirect($this->adminUrl('companies'));
            $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
            $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);

            // Restore preserves the original subscription history.
            $this->patch($this->adminUrl("archives/tenants/restore/{$tenant->id}"))->assertStatus(302);
            $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'deleted_at' => null]);
            $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);

            $this->delete($this->adminUrl("companies/{$tenant->id}"))->assertRedirect($this->adminUrl('companies'));
            $this->delete($this->adminUrl("archives/tenants/force-delete/{$tenant->id}"))->assertStatus(302);
            $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
            $tenant = null;
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }

            if ($tenantDatabase && preg_match('/^[A-Za-z0-9_]+$/', $tenantDatabase)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS '.$tenantDatabase);
            }
        }
    }

    /**
     * Test 6: Coupons Management CRUD, Details & Archive Lifecycle
     */
    public function test_admin_coupons_crud_and_archive_lifecycle(): void
    {
        // 1. Index
        $this->get($this->adminUrl('coupons'))->assertStatus(200);

        // 2. Create view
        $this->get($this->adminUrl('coupons/create'))->assertStatus(200);

        // 3. Store
        $code = 'LEVAN'.rand(1000, 9999);
        $storeResponse = $this->post($this->adminUrl('coupons'), [
            'name' => 'خصم الافتتاح',
            'code' => $code,
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_discount' => 500,
            'expiry_date' => now()->addMonths(3)->toDateString(),
            'usage_limit' => 100,
            'is_active' => 1,
        ]);
        $storeResponse->assertStatus(302);

        $coupon = Coupon::where('code', $code)->first();
        $this->assertNotNull($coupon, 'Coupon was not created in database.');

        // 4. Details endpoint
        $this->get($this->adminUrl("coupons/details/{$coupon->id}"))->assertStatus(200);

        // 5. Edit view
        $this->get($this->adminUrl("coupons/{$coupon->id}/edit"))->assertStatus(200);

        // 6. Update
        $this->put($this->adminUrl("coupons/{$coupon->id}"), [
            'name' => 'خصم الافتتاح المحدث',
            'code' => $code,
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => 1,
        ])->assertStatus(302);
        $coupon->refresh();
        $this->assertEquals(25, (float) $coupon->discount_value);

        // 7. Soft Delete
        $this->delete($this->adminUrl("coupons/{$coupon->id}"))->assertStatus(302);
        $coupon->refresh();
        $this->assertNotNull($coupon->deleted_at);

        // 8. Archive Index
        $this->get($this->adminUrl('archives/coupons'))->assertStatus(200);

        // 9. Restore
        $this->patch($this->adminUrl("archives/coupons/restore/{$coupon->id}"))->assertStatus(302);
        $coupon->refresh();
        $this->assertNull($coupon->deleted_at);

        // 10. Force Delete
        $this->delete($this->adminUrl("coupons/{$coupon->id}"));
        $this->delete($this->adminUrl("archives/coupons/force-delete/{$coupon->id}"))->assertStatus(302);
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    /**
     * Test 7: Subscriptions Management, Tenant Access & Bills Lifecycle
     */
    public function test_admin_subscriptions_and_bills_lifecycle(): void
    {
        // 1. Companies & Subscriptions Index & Create view
        $this->get($this->adminUrl('companies'))->assertStatus(200);
        $this->get($this->adminUrl('companies/create'))->assertStatus(200);

        $this->get($this->adminUrl('subscriptions'))->assertStatus(200);
        $this->get($this->adminUrl('subscriptions/create'))->assertStatus(200);

        $tenant = Tenant::first();
        if ($tenant) {
            $this->get($this->adminUrl("companies/{$tenant->id}/edit"))->assertStatus(200);
            $this->get($this->adminUrl("companies/{$tenant->id}"))->assertStatus(200);
        }

        // 2. Tenants Archive
        $this->get($this->adminUrl('archives/tenants'))->assertStatus(200);

        // 3. Bills Index & Archive
        $this->get($this->adminUrl('bills'))->assertStatus(200);
        $this->get($this->adminUrl('archives/bills'))->assertStatus(200);

        $bill = Bill::first();
        if ($bill) {
            $this->get($this->adminUrl("bills/{$bill->id}"))->assertStatus(200);
            $this->get($this->adminUrl("bills/{$bill->id}/print"))->assertStatus(200);
        }
    }

    /**
     * Test 10: Activity Logs & DataTables AJAX endpoints across all admin modules
     */
    public function test_admin_activity_logs_and_datatables_endpoints(): void
    {
        // 1. Activity Logs views & AJAX
        $this->get($this->adminUrl('activity-logs'))->assertStatus(200);
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get($this->adminUrl('activity-logs'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');

        // 2. DataTables AJAX endpoints across all admin modules
        $endpoints = [
            'users',
            'roles',
            'settings/banks',
            'settings/domains',
            'plans',
            'companies',
            'coupons',
            'bills',
            'archives',
            'archives/plans',
            'archives/coupons',
            'archives/tenants',
            'subscriptions',
            'archives/bills',
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->adminUrl($endpoint).'?draw=1&start=0&length=10';
            $response = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json, text/javascript, */*',
            ])->get($url);

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/json');

            $data = $response->json();
            $this->assertArrayHasKey('draw', $data, "Admin endpoint {$endpoint} missing draw key");
            $this->assertArrayHasKey('recordsTotal', $data, "Admin endpoint {$endpoint} missing recordsTotal key");
            $this->assertArrayHasKey('recordsFiltered', $data, "Admin endpoint {$endpoint} missing recordsFiltered key");
            $this->assertArrayHasKey('data', $data, "Admin endpoint {$endpoint} missing data key");
            $this->assertIsArray($data['data'], "Admin endpoint {$endpoint} data is not an array");
        }
    }

    /**
     * Test 11: Admin Two-Factor Authentication Lifecycle & Settings
     */
    public function test_admin_two_factor_and_auth_lifecycle(): void
    {
        // 1. Two-factor settings page
        $this->get($this->adminUrl('two-factor-authentication'))->assertStatus(200);

        // 2. Enable 2FA
        $this->postJson($this->adminUrl('two-factor-authentication'))
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        // 3. QR code endpoint
        $this->getJson($this->adminUrl('two-factor-qr-code'))
            ->assertStatus(200)
            ->assertJsonStructure(['svg']);

        // 4. Recovery codes
        $this->postJson($this->adminUrl('two-factor-recovery-codes'))
            ->assertStatus(200);

        // 5. Disable 2FA
        $this->deleteJson($this->adminUrl('two-factor-authentication'))
            ->assertStatus(200)
            ->assertJson(['status' => 'disabled']);
    }
}

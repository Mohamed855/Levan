<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\CompanySetting;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Companies\CompanyService;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyAndSubscriptionRefactorTest extends TestCase
{
    protected AdminUser $superAdmin;
    protected Plan $plan;
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->superAdmin = AdminUser::where('email', 'superadmin@gmail.com')->first();
        $this->actingAs($this->superAdmin, 'admin');

        $this->plan = Plan::first() ?? Plan::create([
            'name'                        => 'باقة تجريبية للمطورين',
            'slug'                        => 'dev-test-plan-' . uniqid(),
            'price'                       => 100,
            'price_after_discount'        => 100,
            'subscription_duration_type'  => 'limited',
            'subscription_billing_cycle'  => 'monthly',
            'included_users'              => 5,
            'properties_limit'            => 50,
            'storage_quota_gb'            => 10,
            'active_auctions_limit'       => 20,
            'is_active'                   => true,
        ]);

        $this->city = City::first();
    }

    protected function adminUrl(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return $path ? 'http://localhost/admin/' . $path : 'http://localhost/admin';
    }

    /**
     * 1. Company Creation provisions Tenant, CompanySetting, Admin User, Subscription, Period, Seat, Bill
     */
    public function test_company_creation_provisions_all_entities_and_shows_in_show(): void
    {
        $companyService = app(CompanyService::class);
        $commercialNumber = (string) rand(1000000000, 9999999999);

        $data = [
            'company_name'                  => 'شركة اختبار البناء الكامل ' . rand(1000, 9999),
            'commercial_number'             => $commercialNumber,
            'tax_number'                    => (string) rand(100000000000000, 999999999999999),
            'city_id'                       => $this->city->id,
            'address'                       => 'الرياض، طريق الملك فهد',
            'name'                          => 'مدير شركة اختبار البناء',
            'phone'                         => '05' . rand(10000000, 99999999),
            'email'                         => 'builder-' . uniqid() . '@example.test',
            'password'                      => 'Password123!',
            'password_confirmation'         => 'Password123!',
            'plan_id'                       => $this->plan->id,
            'start_date'                    => now()->toDateString(),
            'payment_type'                  => 'paid',
            'payment_method'                => 'bank_transfer',
            'activate_immediately'          => 1,
            'email_notifications_enabled'   => 1,
            'two_factor_enabled'            => 1,
            'external_integrations_enabled' => 0,
        ];

        $tenant = null;
        $tenantDb = null;

        try {
            $createdTenant = $companyService->createCompany($data);
            $tenant = $createdTenant;
            $tenantDb = $tenant->db_database;

            // 1. Verify Tenant
            $this->assertDatabaseHas('tenants', [
                'id'                   => $tenant->id,
                'commercial_number'    => $commercialNumber,
                'primary_contact_name' => $data['name'],
                'primary_contact_email'=> $data['email'],
                'status'               => 'active',
            ]);

            // 2. Verify CompanySetting
            $this->assertDatabaseHas('company_settings', [
                'tenant_id'                   => $tenant->id,
                'email_notifications_enabled' => 1,
                'two_factor_enabled'          => 1,
                'external_integrations_enabled' => 0,
            ]);

            // 3. Verify Subscription & latestOfMany relationship
            $subscription = $tenant->lastSubscription;
            $this->assertNotNull($subscription);
            $this->assertEquals($tenant->id, $subscription->tenant_id);
            $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);

            // 4. Verify Period & Seat
            $this->assertDatabaseHas('subscription_periods', [
                'subscription_id' => $subscription->id,
            ]);
            $this->assertDatabaseHas('license_seats', [
                'subscription_id' => $subscription->id,
                'tenant_id'       => $tenant->id,
            ]);

            // 5. Verify Show route
            $response = $this->get($this->adminUrl("companies/{$tenant->id}"));
            $response->assertStatus(200);
            $response->assertSee($tenant->company_name);
            $response->assertSee($commercialNumber);
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }
            if ($tenantDb && preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDb);
            }
        }
    }

    /**
     * 2. Updating company modifies company data and settings only, leaves subscription intact
     */
    public function test_company_update_does_not_modify_subscription(): void
    {
        $companyService = app(CompanyService::class);
        $commercialNumber = (string) rand(1000000000, 9999999999);

        $tenant = null;
        $tenantDb = null;

        try {
            $tenant = $companyService->createCompany([
                'company_name'                  => 'شركة قبل التعديل ' . rand(1000, 9999),
                'commercial_number'             => $commercialNumber,
                'tax_number'                    => (string) rand(100000000000000, 999999999999999),
                'city_id'                       => $this->city->id,
                'address'                       => 'العنوان القديم',
                'name'                          => 'مسؤول قبل التعديل',
                'phone'                         => '05' . rand(10000000, 99999999),
                'email'                         => 'edit-' . uniqid() . '@example.test',
                'password'                      => 'Password123!',
                'password_confirmation'         => 'Password123!',
                'plan_id'                       => $this->plan->id,
                'start_date'                    => now()->toDateString(),
                'payment_type'                  => 'paid',
                'payment_method'                => 'bank_transfer',
                'activate_immediately'          => 1,
            ]);
            $tenantDb = $tenant->db_database;
            $originalSub = $tenant->lastSubscription;
            $originalSubEndsAt = $originalSub->ends_at;

            // Update company
            $updated = $companyService->updateCompany($tenant, [
                'company_name'      => 'شركة بعد التعديل',
                'address'           => 'العنوان المحدث والجديد',
                'city_id'           => $this->city->id,
                'tax_number'        => $tenant->tax_number,
                'commercial_number' => $tenant->commercial_number,
                'email_notifications_enabled' => 0,
            ]);

            $this->assertEquals('شركة بعد التعديل', $updated->company_name);
            $this->assertEquals('العنوان المحدث والجديد', $updated->address);
            $this->assertDatabaseHas('company_settings', [
                'tenant_id' => $tenant->id,
                'email_notifications_enabled' => 0,
            ]);

            // Ensure subscription remained untouched
            $freshSub = $tenant->fresh()->lastSubscription;
            $this->assertEquals($originalSub->id, $freshSub->id);
            $this->assertEquals($originalSubEndsAt, $freshSub->ends_at);
            $this->assertEquals($originalSub->plan_id, $freshSub->plan_id);
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }
            if ($tenantDb && preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDb);
            }
        }
    }

    /**
     * 3. Company soft delete hard-deletes subscriptions; restore restores tenant only
     */
    public function test_company_soft_delete_and_restore_hard_deletes_subscriptions(): void
    {
        $companyService = app(CompanyService::class);
        $commercialNumber = (string) rand(1000000000, 9999999999);

        $tenant = null;
        $tenantDb = null;

        try {
            $tenant = $companyService->createCompany([
                'company_name'                  => 'شركة اختبار الحذف ' . rand(1000, 9999),
                'commercial_number'             => $commercialNumber,
                'tax_number'                    => (string) rand(100000000000000, 999999999999999),
                'city_id'                       => $this->city->id,
                'address'                       => 'العنوان',
                'name'                          => 'المسؤول',
                'phone'                         => '05' . rand(10000000, 99999999),
                'email'                         => 'delete-' . uniqid() . '@example.test',
                'password'                      => 'Password123!',
                'password_confirmation'         => 'Password123!',
                'plan_id'                       => $this->plan->id,
                'start_date'                    => now()->toDateString(),
                'payment_type'                  => 'paid',
                'payment_method'                => 'bank_transfer',
                'activate_immediately'          => 1,
            ]);
            $tenantDb = $tenant->db_database;
            $subId = $tenant->lastSubscription->id;

            // Soft-delete company
            $companyService->deleteCompany($tenant);

            $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
            // Subscriptions MUST be hard-deleted
            $this->assertDatabaseMissing('subscriptions', ['id' => $subId]);

            // Restore company
            $restoredTenant = $companyService->restoreCompany($tenant->id);
            $this->assertFalse($restoredTenant->trashed());
            $this->assertDatabaseMissing('subscriptions', ['id' => $subId]);
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }
            if ($tenantDb && preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDb);
            }
        }
    }

    /**
     * 4. Subscription creation rules: cannot create unless last subscription is expired
     */
    public function test_subscription_creation_business_rules(): void
    {
        $companyService = app(CompanyService::class);
        $subscriptionService = app(AdminSubscriptionService::class);
        $commercialNumber = (string) rand(1000000000, 9999999999);

        $tenant = null;
        $tenantDb = null;

        try {
            $tenant = $companyService->createCompany([
                'company_name'                  => 'شركة اختبار قواعد الاشتراك ' . rand(1000, 9999),
                'commercial_number'             => $commercialNumber,
                'tax_number'                    => (string) rand(100000000000000, 999999999999999),
                'city_id'                       => $this->city->id,
                'address'                       => 'العنوان',
                'name'                          => 'المسؤول',
                'phone'                         => '05' . rand(10000000, 99999999),
                'email'                         => 'subrules-' . uniqid() . '@example.test',
                'password'                      => 'Password123!',
                'password_confirmation'         => 'Password123!',
                'plan_id'                       => $this->plan->id,
                'start_date'                    => now()->toDateString(),
                'payment_type'                  => 'paid',
                'payment_method'                => 'bank_transfer',
                'activate_immediately'          => 1,
            ]);
            $tenantDb = $tenant->db_database;

            // 1. Try to create subscription while current subscription is ACTIVE -> must fail validation
            $response = $this->post($this->adminUrl('subscriptions'), [
                'company_id'   => $tenant->id,
                'plan_id'      => $this->plan->id,
                'start_date'   => now()->toDateString(),
                'payment_type' => 'paid',
                'payment_method' => 'bank_transfer',
            ]);
            $response->assertSessionHasErrors('company_id');

            // 2. Mark current subscription as EXPIRED
            $activeSub = $tenant->lastSubscription;
            $activeSub->status = SubscriptionStatus::EXPIRED;
            $activeSub->save();

            // 3. Now creating subscription should succeed
            $newSub = $subscriptionService->createSubscription($tenant, [
                'plan_id'              => $this->plan->id,
                'start_date'           => now()->toDateString(),
                'payment_type'         => 'paid',
                'payment_method'       => 'bank_transfer',
                'activate_immediately' => 1,
            ]);

            $this->assertNotNull($newSub);
            $this->assertNotEquals($activeSub->id, $newSub->id);
            // Verify new subscription is now the latestOfMany
            $this->assertEquals($newSub->id, $tenant->fresh()->lastSubscription->id);
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }
            if ($tenantDb && preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDb);
            }
        }
    }

    /**
     * 5. Toggle subscription status toggles between active/suspended and syncs tenant status
     */
    public function test_subscription_toggle_status_syncs_tenant_status(): void
    {
        $companyService = app(CompanyService::class);
        $subscriptionService = app(AdminSubscriptionService::class);
        $commercialNumber = (string) rand(1000000000, 9999999999);

        $tenant = null;
        $tenantDb = null;

        try {
            $tenant = $companyService->createCompany([
                'company_name'                  => 'شركة اختبار التبديل ' . rand(1000, 9999),
                'commercial_number'             => $commercialNumber,
                'tax_number'                    => (string) rand(100000000000000, 999999999999999),
                'city_id'                       => $this->city->id,
                'address'                       => 'العنوان',
                'name'                          => 'المسؤول',
                'phone'                         => '05' . rand(10000000, 99999999),
                'email'                         => 'toggle-' . uniqid() . '@example.test',
                'password'                      => 'Password123!',
                'password_confirmation'         => 'Password123!',
                'plan_id'                       => $this->plan->id,
                'start_date'                    => now()->toDateString(),
                'payment_type'                  => 'paid',
                'payment_method'                => 'bank_transfer',
                'activate_immediately'          => 1,
            ]);
            $tenantDb = $tenant->db_database;
            $sub = $tenant->lastSubscription;

            $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
            $this->assertEquals('active', $tenant->fresh()->status);

            // Toggle to suspended
            $subscriptionService->toggleStatus($sub);
            $this->assertEquals(SubscriptionStatus::SUSPENDED, $sub->fresh()->status);
            $this->assertEquals('suspended', $tenant->fresh()->status);

            // Toggle back to active
            $subscriptionService->toggleStatus($sub);
            $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->fresh()->status);
            $this->assertEquals('active', $tenant->fresh()->status);
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }
            if ($tenantDb && preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDb);
            }
        }
    }

    /**
     * 6. Hard delete subscription removes subscription and periods/seats
     */
    public function test_subscription_hard_delete(): void
    {
        $companyService = app(CompanyService::class);
        $subscriptionService = app(AdminSubscriptionService::class);
        $commercialNumber = (string) rand(1000000000, 9999999999);

        $tenant = null;
        $tenantDb = null;

        try {
            $tenant = $companyService->createCompany([
                'company_name'                  => 'شركة اختبار الحذف المباشر ' . rand(1000, 9999),
                'commercial_number'             => $commercialNumber,
                'tax_number'                    => (string) rand(100000000000000, 999999999999999),
                'city_id'                       => $this->city->id,
                'address'                       => 'العنوان',
                'name'                          => 'المسؤول',
                'phone'                         => '05' . rand(10000000, 99999999),
                'email'                         => 'harddel-' . uniqid() . '@example.test',
                'password'                      => 'Password123!',
                'password_confirmation'         => 'Password123!',
                'plan_id'                       => $this->plan->id,
                'start_date'                    => now()->toDateString(),
                'payment_type'                  => 'paid',
                'payment_method'                => 'bank_transfer',
                'activate_immediately'          => 1,
            ]);
            $tenantDb = $tenant->db_database;
            $sub = $tenant->lastSubscription;
            $subId = $sub->id;

            $subscriptionService->deleteSubscription($sub);

            $this->assertDatabaseMissing('subscriptions', ['id' => $subId]);
            $this->assertDatabaseMissing('subscription_periods', ['subscription_id' => $subId]);
            $this->assertDatabaseMissing('license_seats', ['subscription_id' => $subId]);
        } finally {
            if ($tenant) {
                Tenant::withTrashed()->whereKey($tenant->id)->forceDelete();
            }
            if ($tenantDb && preg_match('/^[A-Za-z0-9_]+$/', $tenantDb)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDb);
            }
        }
    }

    /**
     * 7. Confirm that subscription show, edit, and update routes do not exist
     */
    public function test_no_subscription_show_edit_update_routes_exist(): void
    {
        $this->assertFalse(Route::has('admin.subscriptions.show'));
        $this->assertFalse(Route::has('admin.subscriptions.edit'));
        $this->assertFalse(Route::has('admin.subscriptions.update'));
    }

    /**
     * 8. Wizard Drafts: can save and retrieve draft, passwords are not stored
     */
    public function test_wizard_drafts_system_for_company_and_subscription(): void
    {
        // 1. Save company draft
        $response = $this->postJson('/admin/wizard-drafts', [
            'resource_key'          => 'admin.company-create',
            'step'                  => '2',
            'company_name'          => 'مسودة شركة جديدة',
            'commercial_number'     => '1010123456',
            'password'              => 'ShouldNotBeSaved',
            'password_confirmation' => 'ShouldNotBeSaved',
        ]);
        $response->assertStatus(200);
        $draftId = $response->json('draft_id');
        $this->assertNotNull($draftId);

        // 2. Retrieve draft
        $getRes = $this->getJson('/admin/wizard-drafts?resource_key=admin.company-create');
        $getRes->assertStatus(200);
        $data = $getRes->json('data');
        $this->assertEquals('مسودة شركة جديدة', $data['company_name']);
        $this->assertArrayNotHasKey('password', $data);

        // 3. Save subscription draft
        $subDraftRes = $this->postJson('/admin/wizard-drafts', [
            'resource_key' => 'admin.subscription-create',
            'step'         => '1',
            'company_id'   => 'test',
        ]);
        $subDraftRes->assertStatus(200);
        $this->assertNotNull($subDraftRes->json('draft_id'));
    }

    /**
     * 9. Permissions: Unauthorized users get 403
     */
    public function test_permissions_unauthorized_user_gets_403(): void
    {
        $unauthorizedAdmin = AdminUser::create([
            'name'     => 'مشرف بدون صلاحيات',
            'email'    => 'no-perm-' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'phone'    => '05' . rand(10000000, 99999999),
            'status'   => \App\Enums\Admin\Users\AdminUserStatus::ACTIVE,
        ]);

        $this->actingAs($unauthorizedAdmin, 'admin');

        $this->get($this->adminUrl('companies'))->assertStatus(403);
        $this->get($this->adminUrl('companies/create'))->assertStatus(403);
        $this->get($this->adminUrl('subscriptions'))->assertStatus(403);
        $this->get($this->adminUrl('subscriptions/create'))->assertStatus(403);

        $unauthorizedAdmin->delete();
    }
}

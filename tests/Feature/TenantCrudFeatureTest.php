<?php

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantCrudFeatureTest extends TestCase
{
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::find('test');
        tenancy()->initialize($this->tenant);

        $this->user = User::where('email', 'demo@levan.com')->first();
        $this->actingAs($this->user, 'tenant');
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    protected function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }

    /**
     * Test 1: Branch CRUD Lifecycle
     */
    public function test_branches_crud_lifecycle(): void
    {
        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('branches'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('branches/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $code = 'TESTBR' . rand(100, 999);
        $storeResponse = $this->post($this->tenantUrl('branches'), [
            'name'    => 'فرع الاختبار المؤقت',
            'code'    => $code,
            'city'    => 'الرياض',
            'phone'   => '0512345678',
            'address' => 'شارع الملك فهد',
        ]);
        $storeResponse->assertStatus(302);

        $branch = Branch::where('code', $code)->first();
        $this->assertNotNull($branch, 'Branch was not created in database.');

        // 4. Edit view
        $editResponse = $this->get($this->tenantUrl("branches/{$branch->id}/edit"));
        $editResponse->assertStatus(200);

        // 5. Update
        $updateResponse = $this->put($this->tenantUrl("branches/{$branch->id}"), [
            'name'    => 'فرع الاختبار المحدث',
            'code'    => $code,
            'city'    => 'جدة',
            'phone'   => '0598765432',
            'address' => 'شارع التحلية',
        ]);
        $updateResponse->assertStatus(302);
        $branch->refresh();
        $this->assertEquals('فرع الاختبار المحدث', $branch->name);

        // 6. Toggle Status
        $toggleResponse = $this->patch($this->tenantUrl("branches/{$branch->id}/toggle-status"));
        $toggleResponse->assertStatus(200);
        $toggleResponse->assertJson(['success' => true]);

        // 7. Destroy
        $destroyResponse = $this->delete($this->tenantUrl("branches/{$branch->id}"));
        $destroyResponse->assertStatus(302);
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    /**
     * Test 2: Team CRUD Lifecycle
     */
    public function test_teams_crud_lifecycle(): void
    {
        $branch = Branch::first();

        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('teams'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('teams/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $teamName = 'فريق تحصيل اختباري ' . rand(100, 999);
        $storeResponse = $this->post($this->tenantUrl('teams'), [
            'name'        => $teamName,
            'branch_id'   => $branch->id,
            'description' => 'وصف الفريق للاختبار',
            'status'      => 'active',
        ]);
        $storeResponse->assertStatus(302);

        $team = Team::where('name', $teamName)->first();
        $this->assertNotNull($team, 'Team was not created in database.');

        // 4. Show view
        $showResponse = $this->get($this->tenantUrl("teams/{$team->id}"));
        $showResponse->assertStatus(200);

        // 5. Edit view
        $editResponse = $this->get($this->tenantUrl("teams/{$team->id}/edit"));
        $editResponse->assertStatus(200);

        // 6. Update
        $updatedName = $teamName . ' معدل';
        $updateResponse = $this->put($this->tenantUrl("teams/{$team->id}"), [
            'name'        => $updatedName,
            'branch_id'   => $branch->id,
            'description' => 'وصف الفريق المحدث',
            'status'      => 'active',
        ]);
        $updateResponse->assertStatus(302);
        $team->refresh();
        $this->assertEquals($updatedName, $team->name);

        // 7. Toggle Status
        $toggleResponse = $this->patch($this->tenantUrl("teams/{$team->id}/toggle-status"));
        $toggleResponse->assertStatus(200);
        $toggleResponse->assertJson(['success' => true]);

        // 8. Destroy
        $destroyResponse = $this->delete($this->tenantUrl("teams/{$team->id}"));
        $destroyResponse->assertStatus(302);
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    /**
     * Test 3: Role CRUD Lifecycle
     */
    public function test_roles_crud_lifecycle(): void
    {
        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('roles'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('roles/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $roleSuffix = chr(rand(97, 122)) . chr(rand(97, 122)) . chr(rand(97, 122));
        $roleName = 'دور تجريبي ' . $roleSuffix;
        $storeResponse = $this->post($this->tenantUrl('roles'), [
            'name'        => $roleName,
            'description' => 'وصف الدور التجريبي',
            'permissions' => ['لوحة تحكم الإدارة'],
        ]);
        $storeResponse->assertStatus(302);

        $role = Role::where('name', $roleName)->first();
        $this->assertNotNull($role, 'Role was not created in database.');

        // 4. Show view
        $showResponse = $this->get($this->tenantUrl("roles/{$role->id}"));
        $showResponse->assertStatus(200);

        // 5. Edit view
        $editResponse = $this->get($this->tenantUrl("roles/{$role->id}/edit"));
        $editResponse->assertStatus(200);

        // 6. Update
        $updatedRoleName = $roleName . ' معدل';
        $updateResponse = $this->put($this->tenantUrl("roles/{$role->id}"), [
            'name'        => $updatedRoleName,
            'description' => 'وصف الدور بعد التعديل',
            'permissions' => ['لوحة تحكم الإدارة'],
        ]);
        $updateResponse->assertStatus(302);
        $role->refresh();
        $this->assertEquals($updatedRoleName, $role->name);

        // 7. Destroy
        $destroyResponse = $this->delete($this->tenantUrl("roles/{$role->id}"));
        $destroyResponse->assertStatus(302);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /**
     * Test 4: Users CRUD & Archive Lifecycle (Create -> Edit -> Show -> Soft Delete -> Restore -> Permanently Delete)
     */
    public function test_users_crud_and_archive_lifecycle(): void
    {
        $branch = Branch::first();
        $role = Role::first();

        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('users'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('users/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $uniqueId = rand(1000, 9999);
        $email = "testuser{$uniqueId}@levan.com";
        $userName = 'مستخدم اختباري ' . chr(rand(97, 122)) . chr(rand(97, 122));
        $storeResponse = $this->post($this->tenantUrl('users'), [
            'name'                  => $userName,
            'employee_number'       => "EMP{$uniqueId}",
            'email'                 => $email,
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone'                 => '05555' . rand(10000, 99999),
            'branch_id'             => $branch->id,
            'role_id'               => $role->id,
            'status'                => 'active',
        ]);
        $storeResponse->assertStatus(302);

        $testUser = User::where('email', $email)->first();
        $this->assertNotNull($testUser, 'User was not created in database.');

        // 4. Show view
        $showResponse = $this->get($this->tenantUrl("users/{$testUser->id}"));
        $showResponse->assertStatus(200);

        // 5. Edit view
        $editResponse = $this->get($this->tenantUrl("users/{$testUser->id}/edit"));
        $editResponse->assertStatus(200);

        // 6. Update
        $updatedName = $userName . ' معدل';
        $updateResponse = $this->put($this->tenantUrl("users/{$testUser->id}"), [
            'name'            => $updatedName,
            'employee_number' => "EMP{$uniqueId}",
            'email'           => $email,
            'phone'           => '05555' . rand(10000, 99999),
            'branch_id'       => $branch->id,
            'role_id'         => $role->id,
            'status'          => 'active',
        ]);
        $updateResponse->assertStatus(302);
        $testUser->refresh();
        $this->assertEquals($updatedName, $testUser->name);

        // 7. Soft Delete (Destroy)
        $destroyResponse = $this->delete($this->tenantUrl("users/{$testUser->id}"));
        $destroyResponse->assertStatus(302);
        $testUser->refresh();
        $this->assertNotNull($testUser->deleted_at, 'User was not soft-deleted.');

        // 8. Archive Index
        $archiveIndexResponse = $this->get($this->tenantUrl('archive/users'));
        $archiveIndexResponse->assertStatus(200);

        // 9. Restore
        $restoreResponse = $this->patch($this->tenantUrl("archive/users/{$testUser->id}/restore"));
        $restoreResponse->assertStatus(302);
        $testUser->refresh();
        $this->assertNull($testUser->deleted_at, 'User was not restored.');

        // 10. Permanently Delete (Force Delete)
        // Soft delete again first
        $this->delete($this->tenantUrl("users/{$testUser->id}"));
        $forceDeleteResponse = $this->delete($this->tenantUrl("archive/users/{$testUser->id}/force-delete"));
        $forceDeleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('users', ['id' => $testUser->id]);
    }

    /**
     * Test 5: Companies CRUD & Archive Lifecycle (Create -> Edit -> Show -> Soft Delete -> Restore -> Permanently Delete)
     */
    public function test_companies_crud_and_archive_lifecycle(): void
    {
        Storage::fake('public');

        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('companies'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('companies/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $companyName = 'شركة الاختبار المتحدة ' . rand(100, 999);
        $fakeLogo = UploadedFile::fake()->image('company_logo.png', 100, 100);
        $storeResponse = $this->post($this->tenantUrl('companies'), [
            'name'              => $companyName,
            'logo'              => $fakeLogo,
            'target_percentage' => 75,
            'status'            => 'active',
        ]);
        $storeResponse->assertStatus(302);

        $company = Company::where('name', $companyName)->first();
        $this->assertNotNull($company, 'Company was not created in database.');

        // 4. Show view (renders with all tabs and relations)
        $showResponse = $this->get($this->tenantUrl("companies/{$company->id}"));
        $showResponse->assertStatus(200);

        // 5. Edit view
        $editResponse = $this->get($this->tenantUrl("companies/{$company->id}/edit"));
        $editResponse->assertStatus(200);

        // 6. Update
        $updatedCompanyName = $companyName . ' محدثة';
        $updateResponse = $this->put($this->tenantUrl("companies/{$company->id}"), [
            'name'              => $updatedCompanyName,
            'target_percentage' => 85,
            'status'            => 'active',
        ]);
        $updateResponse->assertStatus(302);
        $company->refresh();
        $this->assertEquals($updatedCompanyName, $company->name);

        // 7. Soft Delete (Destroy)
        $destroyResponse = $this->delete($this->tenantUrl("companies/{$company->id}"));
        $destroyResponse->assertStatus(302);
        $company->refresh();
        $this->assertNotNull($company->deleted_at, 'Company was not soft-deleted.');

        // 8. Archive Index
        $archiveIndexResponse = $this->get($this->tenantUrl('archive/companies'));
        $archiveIndexResponse->assertStatus(200);

        // 9. Restore
        $restoreResponse = $this->patch($this->tenantUrl("archive/companies/{$company->id}/restore"));
        $restoreResponse->assertStatus(302);
        $company->refresh();
        $this->assertNull($company->deleted_at, 'Company was not restored.');

        // 10. Force Delete (Permanently Delete)
        $this->delete($this->tenantUrl("companies/{$company->id}"));
        $forceDeleteResponse = $this->delete($this->tenantUrl("archive/companies/{$company->id}/force-delete"));
        $forceDeleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    /**
     * Test 6: Portfolios CRUD & Archive Lifecycle (Create -> Edit -> Show -> Soft Delete -> Restore -> Permanently Delete)
     */
    public function test_portfolios_crud_and_archive_lifecycle(): void
    {
        $company = Company::first();

        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('portfolios'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('portfolios/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $portfolioName = 'محفظة الاختبار ' . rand(100, 999);
        $storeResponse = $this->post($this->tenantUrl('portfolios'), [
            'name'       => $portfolioName,
            'company_id' => $company->id,
            'status'     => 'active',
        ]);
        $storeResponse->assertStatus(302);

        $portfolio = Portfolio::where('name', $portfolioName)->first();
        $this->assertNotNull($portfolio, 'Portfolio was not created in database.');

        // 4. Show view
        $showResponse = $this->get($this->tenantUrl("portfolios/{$portfolio->id}"));
        $showResponse->assertStatus(200);

        // 5. Edit view
        $editResponse = $this->get($this->tenantUrl("portfolios/{$portfolio->id}/edit"));
        $editResponse->assertStatus(200);

        // 6. Update
        $updatedPortfolioName = $portfolioName . ' معدلة';
        $updateResponse = $this->put($this->tenantUrl("portfolios/{$portfolio->id}"), [
            'name'       => $updatedPortfolioName,
            'company_id' => $company->id,
            'status'     => 'active',
        ]);
        $updateResponse->assertStatus(302);
        $portfolio->refresh();
        $this->assertEquals($updatedPortfolioName, $portfolio->name);

        // 7. Soft Delete (Destroy)
        $destroyResponse = $this->delete($this->tenantUrl("portfolios/{$portfolio->id}"));
        $destroyResponse->assertStatus(302);
        $portfolio->refresh();
        $this->assertNotNull($portfolio->deleted_at, 'Portfolio was not soft-deleted.');

        // 8. Archive Index
        $archiveIndexResponse = $this->get($this->tenantUrl('archive/portfolios'));
        $archiveIndexResponse->assertStatus(200);

        // 9. Restore
        $restoreResponse = $this->patch($this->tenantUrl("archive/portfolios/{$portfolio->id}/restore"));
        $restoreResponse->assertStatus(302);
        $portfolio->refresh();
        $this->assertNull($portfolio->deleted_at, 'Portfolio was not restored.');

        // 10. Force Delete (Permanently Delete)
        $this->delete($this->tenantUrl("portfolios/{$portfolio->id}"));
        $forceDeleteResponse = $this->delete($this->tenantUrl("archive/portfolios/{$portfolio->id}/force-delete"));
        $forceDeleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
    }

    /**
     * Test 7: Customers CRUD & Archive Lifecycle (Create -> Edit -> Show -> Soft Delete -> Restore -> Permanently Delete)
     */
    public function test_customers_crud_and_archive_lifecycle(): void
    {
        $company = Company::first();
        $portfolio = Portfolio::first();

        // 1. Index
        $indexResponse = $this->get($this->tenantUrl('customers'));
        $indexResponse->assertStatus(200);

        // 2. Create view
        $createResponse = $this->get($this->tenantUrl('customers/create'));
        $createResponse->assertStatus(200);

        // 3. Store
        $identityNumber = '10' . rand(10000000, 99999999);
        $customerName = 'عميل الاختبار ' . rand(100, 999);
        $storeResponse = $this->post($this->tenantUrl('customers'), [
            'name'            => $customerName,
            'customer_type'   => 'individual',
            'company_id'      => $company->id,
            'portfolio_id'    => $portfolio->id,
            'identity_number' => $identityNumber,
            'phone'           => '05' . rand(10000000, 99999999),
        ]);
        $storeResponse->assertStatus(302);

        $customer = Customer::where('identity_number', $identityNumber)->first();
        $this->assertNotNull($customer, 'Customer was not created in database.');

        // 4. Show view (renders full 124KB customer detail page with follows and payment history)
        $showResponse = $this->get($this->tenantUrl("customers/{$customer->id}"));
        $showResponse->assertStatus(200);

        // 5. Edit view
        $editResponse = $this->get($this->tenantUrl("customers/{$customer->id}/edit"));
        $editResponse->assertStatus(200);

        // 6. Update
        $updatedCustomerName = $customerName . ' معدل';
        $updateResponse = $this->put($this->tenantUrl("customers/{$customer->id}"), [
            'name'            => $updatedCustomerName,
            'customer_type'   => 'individual',
            'company_id'      => $company->id,
            'portfolio_id'    => $portfolio->id,
            'identity_number' => $identityNumber,
        ]);
        $updateResponse->assertStatus(302);
        $customer->refresh();
        $this->assertEquals($updatedCustomerName, $customer->name);

        // 7. Soft Delete (Destroy)
        $destroyResponse = $this->delete($this->tenantUrl("customers/{$customer->id}"));
        $destroyResponse->assertStatus(302);
        $customer->refresh();
        $this->assertNotNull($customer->deleted_at, 'Customer was not soft-deleted.');

        // 8. Archive Index
        $archiveIndexResponse = $this->get($this->tenantUrl('archive/customers'));
        $archiveIndexResponse->assertStatus(200);

        // 9. Restore
        $restoreResponse = $this->patch($this->tenantUrl("archive/customers/{$customer->id}/restore"));
        $restoreResponse->assertStatus(302);
        $customer->refresh();
        $this->assertNull($customer->deleted_at, 'Customer was not restored.');

        // 10. Force Delete (Permanently Delete)
        $this->delete($this->tenantUrl("customers/{$customer->id}"));
        $forceDeleteResponse = $this->delete($this->tenantUrl("archive/customers/{$customer->id}/force-delete"));
        $forceDeleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /**
     * Test 8: General Settings CRUD (Countries, Payment Methods, Contact Methods, Customer Status, Document Request Reasons)
     */
    public function test_general_settings_crud_lifecycle(): void
    {
        // === A. Countries ===
        $this->get($this->tenantUrl('general-settings/settings-country'))->assertStatus(200);
        $countryName = 'دولة الاختبار ' . rand(100, 999);
        $this->post($this->tenantUrl('general-settings/settings-country'), ['name' => $countryName])->assertStatus(302);
        $country = SettingsCountry::where('name', $countryName)->first();
        $this->assertNotNull($country);
        $this->put($this->tenantUrl("general-settings/settings-country/{$country->id}"), ['name' => $countryName . ' معدلة'])->assertStatus(302);
        $this->delete($this->tenantUrl("general-settings/settings-country/{$country->id}"))->assertStatus(302);
        $this->assertSoftDeleted('settings_countries', ['id' => $country->id]);

        // === B. Payment Methods ===
        $this->get($this->tenantUrl('general-settings/settings-payment-methods'))->assertStatus(200);
        $pmName = 'طريقة دفع الاختبار ' . rand(100, 999);
        $this->post($this->tenantUrl('general-settings/settings-payment-methods'), ['name' => $pmName])->assertStatus(302);
        $pm = SettingsPaymentMethod::where('name', $pmName)->first();
        $this->assertNotNull($pm);
        $this->put($this->tenantUrl("general-settings/settings-payment-methods/{$pm->id}"), ['name' => $pmName . ' معدلة'])->assertStatus(302);
        $this->delete($this->tenantUrl("general-settings/settings-payment-methods/{$pm->id}"))->assertStatus(302);
        $this->assertSoftDeleted('settings_payment_methods', ['id' => $pm->id]);

        // === C. Contact Methods ===
        $this->get($this->tenantUrl('general-settings/settings-contact-methods'))->assertStatus(200);
        $cmName = 'طريقة تواصل الاختبار ' . rand(100, 999);
        $this->post($this->tenantUrl('general-settings/settings-contact-methods'), ['name' => $cmName])->assertStatus(302);
        $cm = SettingsContactMethod::where('name', $cmName)->first();
        $this->assertNotNull($cm);
        $this->put($this->tenantUrl("general-settings/settings-contact-methods/{$cm->id}"), ['name' => $cmName . ' معدلة'])->assertStatus(302);
        $this->delete($this->tenantUrl("general-settings/settings-contact-methods/{$cm->id}"))->assertStatus(302);
        $this->assertSoftDeleted('settings_contact_methods', ['id' => $cm->id]);

        // === D. Customer Status ===
        $this->get($this->tenantUrl('general-settings/settings-customer-status'))->assertStatus(200);
        $csName = 'حالة عميل الاختبار ' . rand(100, 999);
        $this->post($this->tenantUrl('general-settings/settings-customer-status'), ['name' => $csName, 'status' => 'active'])->assertStatus(200)->assertJson(['success' => true]);
        $cs = SettingsCustomerStatus::where('name', $csName)->first();
        $this->assertNotNull($cs);
        $this->put($this->tenantUrl("general-settings/settings-customer-status/{$cs->id}"), ['name' => $csName . ' معدلة', 'status' => 'active'])->assertStatus(200)->assertJson(['success' => true]);
        $this->delete($this->tenantUrl("general-settings/settings-customer-status/{$cs->id}"))->assertStatus(302);
        $this->assertSoftDeleted('settings_customer_status', ['id' => $cs->id]);
    }

    /**
     * Test 9: Verify DataTables AJAX endpoints across the entire system return JSON data correctly
     */
    public function test_datatables_ajax_returns_json_and_records(): void
    {
        $endpoints = [
            'users',
            'branches',
            'teams',
            'roles',
            'companies',
            'portfolios',
            'customers',
            'archive/users',
            'archive/companies',
            'archive/portfolios',
            'archive/customers',
            'archive/contracts',
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->tenantUrl($endpoint) . '?draw=1&start=0&length=10';
            $response = $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept'           => '*/*',
            ])->get($url);

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/json');

            $data = $response->json();
            $this->assertArrayHasKey('draw', $data, "Endpoint {$endpoint} missing draw key");
            $this->assertArrayHasKey('recordsTotal', $data, "Endpoint {$endpoint} missing recordsTotal key");
            $this->assertArrayHasKey('recordsFiltered', $data, "Endpoint {$endpoint} missing recordsFiltered key");
            $this->assertArrayHasKey('data', $data, "Endpoint {$endpoint} missing data key");
            $this->assertIsArray($data['data'], "Endpoint {$endpoint} data is not an array");
        }
    }
}

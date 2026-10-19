<?php

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\BusinessCenter\Company\Company;
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
     * Test 2: Team CRUD Lifecycle
     */
    public function test_teams_crud_lifecycle(): void
    {
        $teamLeader = User::create([
            'name' => 'قائد فريق اختباري',
            'employee_number' => 'LEAD' . random_int(1000, 9999),
            'email' => 'team-leader-' . random_int(1000, 9999) . '@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'license_seat_id' => $this->user->license_seat_id,
        ]);
        $teamMember = User::create([
            'name' => 'عضو فريق اختباري',
            'employee_number' => 'TEAM' . random_int(1000, 9999),
            'email' => 'team-member-' . random_int(1000, 9999) . '@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'license_seat_id' => $this->user->license_seat_id,
        ]);

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
            'description' => 'وصف الفريق للاختبار',
            'members' => [$teamLeader->id, $teamMember->id],
            'leaders' => [$teamLeader->id],
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
            'description' => 'وصف الفريق المحدث',
            'members' => [$teamLeader->id, $teamMember->id],
            'leaders' => [$teamMember->id],
        ]);
        $updateResponse->assertStatus(302);
        $team->refresh();
        $this->assertEquals($updatedName, $team->name);

        // 7. Toggle Status
        $toggleResponse = $this->patch($this->tenantUrl("teams/{$team->id}/toggle-status"), [
            'status' => 'inactive',
        ]);
        $toggleResponse->assertStatus(200);
        $toggleResponse->assertJson(['success' => true]);

        // 8. Destroy
        $destroyResponse = $this->delete($this->tenantUrl("teams/{$team->id}"));
        $destroyResponse->assertStatus(302);
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
        $teamLeader->forceDelete();
        $teamMember->forceDelete();
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
        $roleName = 'custom_role_' . $roleSuffix;
        $storeResponse = $this->post($this->tenantUrl('roles'), [
            'name'        => $roleName,
            'description' => 'وصف الدور التجريبي',
            'permissions' => ['dashboard.view'],
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
        $updatedRoleName = $roleName . '_updated';
        $updateResponse = $this->put($this->tenantUrl("roles/{$role->id}"), [
            'name'        => $updatedRoleName,
            'description' => 'وصف الدور بعد التعديل',
            'permissions' => ['dashboard.view'],
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
     * Test 6: Verify DataTables AJAX endpoints return JSON data correctly
     */
    public function test_datatables_ajax_returns_json_and_records(): void
    {
        $endpoints = [
            'users',
            'teams',
            'roles',
            'companies',
            'archive/users',
            'archive/companies',
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

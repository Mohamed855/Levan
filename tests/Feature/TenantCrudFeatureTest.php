<?php

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
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
     * Test 5: Verify DataTables AJAX endpoints return JSON data correctly
     */
    public function test_datatables_ajax_returns_json_and_records(): void
    {
        $endpoints = [
            'users',
            'roles',
            'archive/users',
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

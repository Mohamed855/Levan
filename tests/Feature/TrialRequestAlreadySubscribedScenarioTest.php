<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\City;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Shared\Cpanel\CpanelService;
use App\Services\Shared\Tenant\TenantCreator;
use App\Services\Shared\Tenant\TenantInitializer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrialRequestAlreadySubscribedScenarioTest extends TestCase
{
    private array $trialIds = [];
    private array $tenantIds = [];

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        foreach (TrialRequest::whereIn('id', $this->trialIds)->get() as $req) {
            $req->delete();
        }
        foreach (Tenant::whereIn('id', $this->tenantIds)->get() as $tenant) {
            $tenant->forceDelete();
        }
        parent::tearDown();
    }

    private function createPendingTrialRequest(array $overrides = []): TrialRequest
    {
        $city = City::first() ?? City::create(['name' => 'الرياض', 'is_active' => true]);

        $req = TrialRequest::create(array_merge([
            'customer_name' => 'عميل تجريبي',
            'customer_email' => 'client-' . Str::lower(Str::random(10)) . '@example.test',
            'customer_phone' => '05' . random_int(10000000, 99999999),
            'company_name' => 'شركة تجريبية ' . Str::random(6),
            'commercial_number' => (string) random_int(1000000000, 1999999999),
            'tax_number' => '3' . random_int(1000000000000, 9999999999999) . '3',
            'city_id' => $city->id,
            'address' => 'شارع الملك فهد',
            'password_hash' => Hash::make('Secret123!'),
            'plain_password' => 'Secret123!',
            'email_verified_at' => now(),
            'status' => TrialRequestStatus::PENDING,
            'provisioning_status' => 'pending',
            'submission_token' => (string) Str::uuid(),
        ], $overrides));

        $this->trialIds[] = $req->id;

        return $req;
    }

    private function createTenantRecord(array $attributes): Tenant
    {
        $tenantId = 'test_tenant_' . random_int(10000, 99999);
        $tenant = Tenant::create(array_merge([
            'id' => $tenantId,
            'folderName' => $tenantId,
            'db_database' => 'db_' . $tenantId,
            'db_username' => 'u_' . $tenantId,
            'db_password' => 'secret',
            'company_name' => 'شركة مسجلة ' . Str::random(6),
            'commercial_number' => (string) random_int(2000000000, 2999999999),
            'tax_number' => '3' . random_int(1000000000000, 9999999999999) . '3',
            'primary_contact_name' => 'مدير الشركة',
            'primary_contact_email' => 'tenant-' . Str::lower(Str::random(8)) . '@example.test',
            'primary_contact_phone' => '05' . random_int(10000000, 99999999),
        ], $attributes));

        $this->tenantIds[] = $tenant->id;

        return $tenant;
    }

    public function test_approving_trial_when_client_data_already_exists_updates_status_to_already_subscribed_and_shows_toast(): void
    {
        $admin = AdminUser::first() ?? AdminUser::factory()->create();

        // 1. Create a pending trial request
        $trial = $this->createPendingTrialRequest([
            'commercial_number' => '1010998877',
            'company_name' => 'شركة الأفق للاختبار',
            'customer_email' => 'horizon@example.test',
            'customer_phone' => '0511223344',
        ]);

        // 2. Later, a tenant is created with the same commercial number
        $tenant = $this->createTenantRecord([
            'commercial_number' => '1010998877',
            'company_name' => 'شركة الأفق للاختبار',
            'primary_contact_email' => 'horizon@example.test',
            'primary_contact_phone' => '0511223344',
        ]);

        // 3. Superadmin attempts to approve the trial request
        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.trial-requests.approve', $trial));

        // 4. Assert redirect back to show with error toast "بيانات العميل مسجلة بالفعل."
        $response->assertRedirect(route('admin.trial-requests.show', $trial));
        $response->assertSessionHas('error', 'بيانات العميل مسجلة بالفعل.');

        // 5. Verify trial request status was updated to already_subscribed and provisioning error is null
        $trial->refresh();
        $this->assertSame(TrialRequestStatus::ALREADY_SUBSCRIBED, $trial->status);
        $this->assertSame($tenant->id, $trial->tenant_id);
        $this->assertNull($trial->provisioning_error);

        // 6. View the trial request show page
        $showResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.trial-requests.show', $trial));

        $showResponse->assertOk();
        $showResponse->assertSee('مشترك بالفعل');
        $showResponse->assertSee('بيانات العميل مسجلة بالفعل كشركة في النظام (مشترك بالفعل)، ولا توجد إجراءات متاحة لهذا الطلب.');
        $showResponse->assertDontSee('موافقة وإنشاء شركة');
        $showResponse->assertDontSee('رفض الطلب');
        $showResponse->assertDontSee('تعذر إكمال إنشاء الحساب');
    }

    public function test_creating_tenant_from_elsewhere_automatically_updates_matching_trial_request_to_already_subscribed(): void
    {
        // 1. Create a pending trial request with unique data
        $trial = $this->createPendingTrialRequest([
            'commercial_number' => '1010556677',
            'tax_number' => '300011122233303',
            'company_name' => 'شركة النخيل الرقمية',
            'customer_email' => 'nakheel@example.test',
            'customer_phone' => '0555667788',
        ]);

        $this->assertSame(TrialRequestStatus::PENDING, $trial->status);

        // 2. Mock CpanelService and TenantInitializer to simulate TenantCreator::createTenantEnvironment
        $mockCpanel = $this->createMock(CpanelService::class);
        $mockCpanel->method('createDatabase')->willReturn([
            'database' => 'levan_test_sync',
            'username' => 'u_test_sync',
            'password' => 'secret',
            'host' => '127.0.0.1',
        ]);

        $mockInitializer = $this->createMock(TenantInitializer::class);
        $mockInitializer->method('setupTenantEnvironment')->willReturn(null);

        $creator = new TenantCreator($mockCpanel, $mockInitializer);

        // Create tenant with matching tax number
        $metadata = [
            'billing_company_name' => 'شركة النخيل الرقمية المختلفة بالاسم',
            'billing_commercial_number' => '1099999999',
            'billing_tax_number' => '300011122233303', // Matches trial!
            'billing_name' => 'مدير النخيل',
            'billing_email' => 'other-email@example.test',
            'billing_phone' => '0599999999',
            'email_notifications_enabled' => false,
            'two_factor_enabled' => false,
            'external_integrations_enabled' => false,
        ];

        [$newTenant, $user] = $creator->createTenantEnvironment($metadata);
        $this->tenantIds[] = $newTenant->id;

        // 3. Assert the trial request was automatically marked as ALREADY_SUBSCRIBED
        $trial->refresh();
        $this->assertSame(TrialRequestStatus::ALREADY_SUBSCRIBED, $trial->status);
        $this->assertSame($newTenant->id, $trial->tenant_id);
        $this->assertNull($trial->provisioning_error);
    }

    public function test_matching_by_phone_candidates_and_case_insensitive_email(): void
    {
        $admin = AdminUser::first() ?? AdminUser::factory()->create();

        // Trial has 05... phone and lowercase email
        $trial = $this->createPendingTrialRequest([
            'customer_phone' => '0567891234',
            'customer_email' => 'saudi.corp@example.test',
        ]);

        // Tenant has 9665... phone format
        $tenant = $this->createTenantRecord([
            'primary_contact_phone' => '966567891234',
            'primary_contact_email' => 'SAUDI.CORP@EXAMPLE.TEST',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.trial-requests.approve', $trial));

        $response->assertRedirect(route('admin.trial-requests.show', $trial));
        $response->assertSessionHas('error', 'بيانات العميل مسجلة بالفعل.');

        $trial->refresh();
        $this->assertSame(TrialRequestStatus::ALREADY_SUBSCRIBED, $trial->status);
        $this->assertSame($tenant->id, $trial->tenant_id);
    }

    public function test_viewing_show_page_auto_syncs_pending_request_if_tenant_already_exists(): void
    {
        $admin = AdminUser::first() ?? AdminUser::factory()->create();

        $trial = $this->createPendingTrialRequest([
            'commercial_number' => '1010334455',
        ]);

        $tenant = $this->createTenantRecord([
            'commercial_number' => '1010334455',
        ]);

        // Viewing the page before clicking approve
        $this->actingAs($admin, 'admin')
            ->get(route('admin.trial-requests.show', $trial))
            ->assertOk()
            ->assertSee('مشترك بالفعل')
            ->assertDontSee('موافقة وإنشاء شركة')
            ->assertDontSee('رفض الطلب');

        $trial->refresh();
        $this->assertSame(TrialRequestStatus::ALREADY_SUBSCRIBED, $trial->status);
        $this->assertSame($tenant->id, $trial->tenant_id);
    }

    public function test_cannot_reject_request_with_already_subscribed_status(): void
    {
        $admin = AdminUser::first() ?? AdminUser::factory()->create();

        $trial = $this->createPendingTrialRequest([
            'status' => TrialRequestStatus::ALREADY_SUBSCRIBED,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Request already processed.');

        app(\App\Actions\Admin\PublicRequests\RejectTrialRequestAction::class)->execute($trial, 'سبب الرفض', $admin);
    }
}

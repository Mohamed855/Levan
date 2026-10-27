<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Client;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\City;
use App\Services\Admin\Clients\ClientService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminClientsFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::where('email', 'superadmin@gmail.com')->first();
        if (! $this->admin) {
            $this->admin = AdminUser::firstOrCreate(
                ['email' => 'admin_client_test@levan.com'],
                [
                    'name' => 'Client Admin Test',
                    'password' => bcrypt('password'),
                    'phone' => '0500000097',
                    'status' => 'active',
                ]
            );
        }
    }

    public function test_client_model_aliases_and_inheritance(): void
    {
        $client = new Client([
            'company_name' => 'شركة لوت للتطوير',
            'primary_contact_name' => 'محمد أحمد',
            'primary_contact_email' => 'client@lot.test',
            'primary_contact_phone' => '0512345678',
        ]);

        $this->assertInstanceOf(Tenant::class, $client);
        $this->assertSame('tenants', $client->getTable());
        $this->assertSame('شركة لوت للتطوير', $client->name);
        $this->assertSame('محمد أحمد', $client->contact_name);
        $this->assertSame('client@lot.test', $client->contact_email);
        $this->assertSame('0512345678', $client->contact_phone);

        // Test mutating aliases
        $client->name = 'الاسم المحدث';
        $client->contact_name = 'مسؤول جديد';
        $this->assertSame('الاسم المحدث', $client->company_name);
        $this->assertSame('مسؤول جديد', $client->primary_contact_name);
    }

    public function test_clients_index_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.clients.index'));

        $response->assertOk()
            ->assertSee('clients-table')
            ->assertSee('إجمالي العملاء');
    }

    public function test_clients_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.clients.create'));

        $response->assertOk()
            ->assertSee('client-wizard');
    }

    public function test_clients_datatable_ajax_returns_json_data(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.clients.index'), [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_client_service_get_statistics_returns_five_buckets(): void
    {
        $service = app(ClientService::class);
        $stats = $service->getCompanyStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('suspended', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('withoutSubscription', $stats);
    }

    public function test_client_commercial_and_tax_number_format_validation(): void
    {
        $city = City::first() ?? City::create(['name' => 'الرياض']);

        // Invalid commercial number (alphabetic) and invalid tax number (doesn't start/end with 3 or not 15 digits)
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.clients.store'), [
                'company_name' => 'شركة تجربة الأرقام',
                'commercial_number' => 'ABC1234567',
                'tax_number' => '100000000000002',
                'city_id' => $city->id,
                'address' => 'الرياض',
                'name' => 'مدير النظام',
                'phone' => '0512345678',
                'email' => 'tax-cr-test@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertSessionHasErrors(['commercial_number', 'tax_number']);

        // Test digit count: 9 digits commercial number, 14 digits tax number
        $response2 = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.clients.store'), [
                'company_name' => 'شركة تجربة الأرقام 2',
                'commercial_number' => '101012345',
                'tax_number' => '30000000000003',
                'city_id' => $city->id,
                'address' => 'الرياض',
                'name' => 'مدير النظام',
                'phone' => '0512345678',
                'email' => 'tax-cr-test2@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response2->assertSessionHasErrors(['commercial_number', 'tax_number']);
    }

    public function test_client_create_immediate_activation_activates_subscription_and_marks_bill_paid(): void
    {
        $plan = Plan::where('is_active', true)->firstOrFail();
        $city = City::first() ?? City::create(['name' => 'الرياض']);
        $commercialNumber = (string) rand(1000000000, 9999999999);
        $taxNumber = '3' . str_pad((string) rand(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT) . '3';
        $email = 'client-' . uniqid() . '@test.com';

        $tenant = null;
        $tenantDatabase = null;

        try {
            $response = $this->actingAs($this->admin, 'admin')
                ->post(route('admin.clients.store'), [
                    'company_name' => 'شركة اختبار التفعيل الفوري ' . rand(100, 999),
                    'commercial_number' => $commercialNumber,
                    'tax_number' => $taxNumber,
                    'city_id' => $city->id,
                    'address' => 'الرياض، العليا',
                    'name' => 'مدير الحساب',
                    'phone' => '05' . rand(10000000, 99999999),
                    'email' => $email,
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                    'plan_id' => $plan->id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'activate_immediately' => '1',
                ]);

            $response->assertRedirect(route('admin.clients.index'));

            $tenant = Tenant::where('commercial_number', $commercialNumber)->firstOrFail();
            $tenantDatabase = $tenant->tenancy_db_name;

            $subscription = $tenant->lastSubscription;
            $this->assertNotNull($subscription);
            $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
            $this->assertTrue($subscription->is_active);

            $bill = $tenant->bills()->latest()->first();
            $this->assertNotNull($bill);
            $this->assertSame(BillStatus::PAID, $bill->status);
        } finally {
            if ($tenant) {
                tenancy()->end();
                $tenant->delete();
            }
            if ($tenantDatabase && preg_match('/^[A-Za-z0-9_]+$/', $tenantDatabase)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDatabase);
            }
        }
    }

    public function test_client_create_without_immediate_activation_suspends_subscription_and_never_sets_pending(): void
    {
        $plan = Plan::where('is_active', true)->firstOrFail();
        $city = City::first() ?? City::create(['name' => 'الرياض']);
        $commercialNumber = (string) rand(1000000000, 9999999999);
        $taxNumber = '3' . str_pad((string) rand(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT) . '3';
        $email = 'client-' . uniqid() . '@test.com';

        $tenant = null;
        $tenantDatabase = null;

        try {
            $response = $this->actingAs($this->admin, 'admin')
                ->post(route('admin.clients.store'), [
                    'company_name' => 'شركة بدون تفعيل فوري ' . rand(100, 999),
                    'commercial_number' => $commercialNumber,
                    'tax_number' => $taxNumber,
                    'city_id' => $city->id,
                    'address' => 'الرياض، السليمانية',
                    'name' => 'مدير الحساب',
                    'phone' => '05' . rand(10000000, 99999999),
                    'email' => $email,
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                    'plan_id' => $plan->id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'activate_immediately' => '0',
                ]);

            $response->assertRedirect(route('admin.clients.index'));

            $tenant = Tenant::where('commercial_number', $commercialNumber)->firstOrFail();
            $tenantDatabase = $tenant->tenancy_db_name;

            $subscription = $tenant->lastSubscription;
            $this->assertNotNull($subscription);
            // Must be SUSPENDED, NEVER PENDING
            $this->assertSame(SubscriptionStatus::SUSPENDED, $subscription->status);
            $this->assertFalse($subscription->is_active);

            $bill = $tenant->bills()->latest()->first();
            $this->assertNotNull($bill);
            $this->assertSame(BillStatus::UNPAID, $bill->status);
        } finally {
            if ($tenant) {
                tenancy()->end();
                $tenant->delete();
            }
            if ($tenantDatabase && preg_match('/^[A-Za-z0-9_]+$/', $tenantDatabase)) {
                DB::connection('mysql')->statement('DROP DATABASE IF EXISTS ' . $tenantDatabase);
            }
        }
    }

    public function test_subscription_is_active_accessor(): void
    {
        $activeSub = new Subscription(['status' => SubscriptionStatus::ACTIVE]);
        $this->assertTrue($activeSub->is_active);

        $suspendedSub = new Subscription(['status' => SubscriptionStatus::SUSPENDED]);
        $this->assertFalse($suspendedSub->is_active);

        $cancelledSub = new Subscription(['status' => SubscriptionStatus::CANCELLED]);
        $this->assertFalse($cancelledSub->is_active);

        $expiredSub = new Subscription(['status' => SubscriptionStatus::EXPIRED]);
        $this->assertFalse($expiredSub->is_active);
    }
}

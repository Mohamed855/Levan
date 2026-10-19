<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Client;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Clients\ClientService;
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
}

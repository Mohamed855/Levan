<?php

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use Tests\TestCase;

class TenantDashboardComprehensiveTest extends TestCase
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
     * Test 1: Tenant Dashboard views and ajax metrics
     */
    public function test_tenant_dashboard_and_metrics(): void
    {
        $response = $this->get($this->tenantUrl('dashboard'));
        $response->assertStatus(200);

        // Chart / performance AJAX endpoints
        $this->getJson($this->tenantUrl('dashboard?period=month'))
            ->assertStatus(200);

        // Sub endpoints
        $this->getJson($this->tenantUrl('dashboard/upcoming-follow-ups'))->assertStatus(200);
        $this->getJson($this->tenantUrl('dashboard/my-upcoming-followups'))->assertStatus(200);
        $this->getJson($this->tenantUrl('dashboard/statistics'))->assertStatus(200);
        $this->getJson($this->tenantUrl('dashboard/approvals'))->assertStatus(200);
        $this->getJson($this->tenantUrl('dashboard/overdue-tasks'))->assertStatus(200);
        $this->getJson($this->tenantUrl('dashboard/my-overdue-tasks'))->assertStatus(200);
        $this->get($this->tenantUrl('dashboard/collection-sessions-page'))->assertStatus(200);
        $this->get($this->tenantUrl('dashboard/excellence'))->assertStatus(200);
        $this->getJson($this->tenantUrl('dashboard/my-performance-comparison'))->assertStatus(200);
    }

    /**
     * Test 2: Settings, Settings Center, and API Token Settings
     */
    public function test_settings_and_center_endpoints(): void
    {
        $this->get($this->tenantUrl('settings'))->assertStatus(200);
        $this->get($this->tenantUrl('settings/center'))->assertStatus(200);
        $this->get($this->tenantUrl('api-token-settings'))->assertStatus(200);

        // Customer status settings index & toggle
        $this->get($this->tenantUrl('general-settings/settings-customer-status'))->assertStatus(200);

        // Document request reasons index
        $this->get($this->tenantUrl('general-settings/settings-document-request-reasons'))->assertStatus(200);

        // Message templates index
        $this->get($this->tenantUrl('general-settings/message-templates'))->assertStatus(200);

        // Payment methods, contact methods, countries
        $this->get($this->tenantUrl('general-settings/settings-payment-methods'))->assertStatus(200);
        $this->get($this->tenantUrl('general-settings/settings-contact-methods'))->assertStatus(200);
        $this->get($this->tenantUrl('general-settings/settings-country'))->assertStatus(200);
    }

    /**
     * Test 3: General Settings CRUD operations
     */
    public function test_general_settings_crud(): void
    {
        $unique = uniqid();

        // 1. Message Template CRUD
        $templateData = [
            'name' => 'قالب تجريبي ' . $unique,
            'content' => 'مرحباً {name}، رصيدك هو {balance}',
            'category' => 'customers',
            'channel' => 'sms',
        ];
        $res = $this->post($this->tenantUrl('general-settings/message-templates'), $templateData);
        $res->assertSessionHasNoErrors();
        $this->assertDatabaseHas('message_templates', ['name' => $templateData['name']]);

        $template = \App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate::where('name', $templateData['name'])->first();
        $this->assertNotNull($template);

        // Preview
        $this->postJson($this->tenantUrl('general-settings/message-templates/preview'), [
            'content' => $template->content,
            'category' => 'customers',
        ])->assertStatus(200);

        // Get templates & variables
        $this->getJson($this->tenantUrl('general-settings/message-templates/get-templates'))->assertStatus(200);
        $this->getJson($this->tenantUrl('general-settings/message-templates/get-variables-by-category?category=customers'))->assertStatus(200);

        // Toggle status
        $this->patchJson($this->tenantUrl("general-settings/message-templates/{$template->id}/toggle-status"))->assertStatus(200);

        // Update
        $this->put($this->tenantUrl("general-settings/message-templates/{$template->id}"), [
            'name' => 'قالب محدث ' . $unique,
            'content' => 'مرحباً {name} المحدث',
            'category' => 'customers',
            'channel' => 'sms',
        ])->assertSessionHasNoErrors();

        // Delete
        $this->delete($this->tenantUrl("general-settings/message-templates/{$template->id}"));
        $this->assertSoftDeleted('message_templates', ['id' => $template->id]);

        // 2. Document Request Reasons CRUD
        $reasonData = ['name' => 'سبب تجريبي ' . $unique];
        $this->post($this->tenantUrl('general-settings/settings-document-request-reasons'), $reasonData)
            ->assertSessionHasNoErrors();
        $reason = \App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason::where('name', $reasonData['name'])->first();
        $this->assertNotNull($reason);

        // Toggle status
        $this->get($this->tenantUrl("general-settings/settings-document-request-reasons/{$reason->id}/edit"));

        // Update
        $this->put($this->tenantUrl("general-settings/settings-document-request-reasons/{$reason->id}"), [
            'name' => 'سبب محدث ' . $unique
        ])->assertSessionHasNoErrors();

        // Delete
        $this->delete($this->tenantUrl("general-settings/settings-document-request-reasons/{$reason->id}"));
        $this->assertSoftDeleted('settings_document_request_reasons', ['id' => $reason->id]);

        // 3. Payment Methods CRUD
        $pmData = ['name' => 'طريقة دفع ' . $unique];
        $this->post($this->tenantUrl('general-settings/settings-payment-methods'), $pmData)
            ->assertSessionHasNoErrors();
        $pm = \App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod::where('name', $pmData['name'])->first();
        $this->assertNotNull($pm);

        $this->put($this->tenantUrl("general-settings/settings-payment-methods/{$pm->id}"), [
            'name' => 'طريقة دفع محدثة ' . $unique
        ])->assertSessionHasNoErrors();

        $this->delete($this->tenantUrl("general-settings/settings-payment-methods/{$pm->id}"));
        $this->assertSoftDeleted('settings_payment_methods', ['id' => $pm->id]);

        // 4. Contact Methods CRUD
        $cmData = ['name' => 'طريقة تواصل ' . $unique];
        $this->post($this->tenantUrl('general-settings/settings-contact-methods'), $cmData)
            ->assertSessionHasNoErrors();
        $cm = \App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod::where('name', $cmData['name'])->first();
        $this->assertNotNull($cm);

        $this->put($this->tenantUrl("general-settings/settings-contact-methods/{$cm->id}"), [
            'name' => 'طريقة تواصل محدثة ' . $unique
        ])->assertSessionHasNoErrors();

        $this->delete($this->tenantUrl("general-settings/settings-contact-methods/{$cm->id}"));
        $this->assertSoftDeleted('settings_contact_methods', ['id' => $cm->id]);

        // 5. Country CRUD
        $countryData = ['name' => 'دولة ' . $unique, 'code' => substr($unique, 0, 8)];
        $this->post($this->tenantUrl('general-settings/settings-country'), $countryData)
            ->assertSessionHasNoErrors();
        $country = \App\Models\Tenants\GeneralSetting\Country\SettingsCountry::where('name', $countryData['name'])->first();
        $this->assertNotNull($country);

        $this->put($this->tenantUrl("general-settings/settings-country/{$country->id}"), [
            'name' => 'دولة محدثة ' . $unique,
            'code' => substr(strrev($unique), 0, 8),
        ])->assertSessionHasNoErrors();

        $this->delete($this->tenantUrl("general-settings/settings-country/{$country->id}"));
        $this->assertSoftDeleted('settings_countries', ['id' => $country->id]);
    }

    /**
     * Test 4: Tasks and My Tasks
     */
    public function test_tasks_and_my_tasks(): void
    {
        $this->get($this->tenantUrl('tasks'))->assertStatus(200);
        $this->get($this->tenantUrl('tasks/export'))->assertSuccessful();

        $this->get($this->tenantUrl('my-tasks'))->assertStatus(200);
        $this->get($this->tenantUrl('my-tasks/export'))->assertSuccessful();
    }

    /**
     * Test 5: Tickets
     */
    public function test_tickets(): void
    {
        $settings = \App\Models\Admin\Settings\Settings::first();
        $originalEnabled = $settings?->tickets_enabled ?? false;

        try {
            // 1. Unconfigured redirects to dashboard with error message
            $settings?->update(['tickets_enabled' => 0]);
            $this->get($this->tenantUrl('tickets'))
                ->assertRedirect($this->tenantUrl('dashboard'));

            // 2. When configured, loads index and create views
            \Illuminate\Support\Facades\Http::fake([
                '*' => \Illuminate\Support\Facades\Http::response(['success' => true, 'data' => ['data' => ['data' => []]]], 200),
            ]);

            $settings?->update([
                'tickets_enabled' => 1,
                'etmam_base_url' => 'https://api.example.com',
                'etmam_api_token' => 'token123',
                'etmam_system_code' => 'SYS123',
                'etmam_hmac_secret' => 'SECRET123',
            ]);

            $this->get($this->tenantUrl('tickets'))->assertStatus(200);
            $this->get($this->tenantUrl('tickets/create'))->assertStatus(200);
        } finally {
            $settings?->update([
                'tickets_enabled' => $originalEnabled,
            ]);
        }
    }

    /**
     * Test 6: Subscriptions and Bills
     */
    public function test_subscriptions_and_bills(): void
    {
        $this->get($this->tenantUrl('subscriptions'))->assertSuccessful();
        $this->get($this->tenantUrl('subscriptions/licenses?new_licenses_count=1'))->assertSuccessful();
        $this->get($this->tenantUrl('subscriptions/renew'))->assertSuccessful();

        $this->get($this->tenantUrl('bills'))->assertSuccessful();
    }

    /**
     * Test 7: Financial Collections, Targets, and Reconciliation
     */
    public function test_financial_collections_and_targets(): void
    {
        $this->get($this->tenantUrl('financial-collections/collections'))->assertStatus(200);
        $this->get($this->tenantUrl('financial-collections/collections/create'))->assertStatus(200);

        $this->get($this->tenantUrl('financial-collections/admin-collections'))->assertStatus(200);

        $this->get($this->tenantUrl('financial-collections/collection-targets'))->assertStatus(200);
        $this->get($this->tenantUrl('financial-collections/collection-targets/create'))->assertStatus(200);

        $this->get($this->tenantUrl('financial-collections/import'))->assertStatus(200);
        $this->get($this->tenantUrl('financial-collections/reconciliation'))->assertStatus(200);
    }

    /**
     * Test 8: Collection Process
     */
    public function test_collection_process(): void
    {
        $this->get($this->tenantUrl('collection-process'))->assertStatus(200);
    }

    /**
     * Test 9: Customer Sub-resources
     */
    public function test_customer_subresources(): void
    {
        $customer = Customer::first();
        $this->assertNotNull($customer);

        // Show & Edit
        $this->get($this->tenantUrl("customers/{$customer->id}"))->assertStatus(200);
        $this->get($this->tenantUrl("customers/{$customer->id}/edit"))->assertStatus(200);

        // Sub-resources lists (AJAX endpoints)
        $this->getJson($this->tenantUrl("customers/{$customer->id}/attachments/list"))->assertStatus(200);
        $this->getJson($this->tenantUrl("customers/{$customer->id}/notes/list"))->assertStatus(200);
        $this->getJson($this->tenantUrl("customers/{$customer->id}/comments/list"))->assertStatus(200);
        $this->getJson($this->tenantUrl("customers/{$customer->id}/payment-plans/list"))->assertStatus(200);
        $this->getJson($this->tenantUrl("customers/{$customer->id}/settlements/list"))->assertStatus(200);
        $this->get($this->tenantUrl("customers/{$customer->id}/payments/create"))->assertStatus(200);
        $this->getJson($this->tenantUrl("customers/{$customer->id}/payment-history/list"))->assertStatus(200);

        // Customer Distributions & Update requests & Import
        $this->get($this->tenantUrl('customers/distribution'))->assertStatus(200);
        $this->get($this->tenantUrl('customers/update-requests'))->assertStatus(200);
        $this->get($this->tenantUrl('customers/import'))->assertStatus(200);
    }

    /**
     * Test 10: Company Letters
     */
    public function test_company_letters(): void
    {
        $this->get($this->tenantUrl('company-letters'))->assertStatus(200);
    }

    /**
     * Test 11: Communication Message Logs
     */
    public function test_communication_message_logs(): void
    {
        $this->get($this->tenantUrl('communication/message-logs'))->assertStatus(200);
        $this->get($this->tenantUrl('communication/message-individual-logs'))->assertStatus(200);
        $this->get($this->tenantUrl('communication/message-individual-logs/archives'))->assertStatus(200);
    }

    /**
     * Test 12: Activity Logs
     */
    public function test_activity_logs(): void
    {
        $this->get($this->tenantUrl('activity-logs'))->assertStatus(200);
    }

    /**
     * Test 13: Archive Endpoints
     */
    public function test_archive_endpoints(): void
    {
        $this->get($this->tenantUrl('archive/customers'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/companies'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/contracts'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/portfolios'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/users'))->assertStatus(200);
        $this->get($this->tenantUrl('activity-logs/archives'))->assertStatus(200);
        $this->get($this->tenantUrl('communication/message-individual-logs/archives'))->assertStatus(200);
    }

    /**
     * Test 14: Reports Endpoints
     */
    public function test_reports_endpoints(): void
    {
        $this->get($this->tenantUrl('reports/customers'))->assertStatus(200);
        $this->get($this->tenantUrl('reports/customer-follows'))->assertStatus(200);
        $this->get($this->tenantUrl('reports/financial'))->assertStatus(200);
        $this->get($this->tenantUrl('reports/companies'))->assertStatus(200);
        $this->get($this->tenantUrl('reports/employees'))->assertStatus(200);
        $this->get($this->tenantUrl('reports/executive'))->assertStatus(200);
    }
}

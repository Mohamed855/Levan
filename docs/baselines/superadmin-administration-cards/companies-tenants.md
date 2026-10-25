Administration: Central Companies & Tenant Provisioning
Boundary: central
Scope / aggregate root: Tenant company accounts, tenant domain initialization, database migration dispatch, tenant status lifecycle
Routes + middleware + guard: routes/admin.php (admin.companies.*, admin.clients.*) + auth:admin + permission:clients.*
Controller: App\Http\Controllers\Admin\Clients\ClientController
FormRequests: App\Http\Requests\Admin\Clients\StoreClientRequest, App\Http\Requests\Admin\Clients\UpdateClientRequest, App\Http\Requests\Admin\Clients\CheckAdminEmailUniqueRequest, App\Http\Requests\Admin\Clients\CheckAdminPhoneUniqueRequest, App\Http\Requests\Admin\Clients\CheckCommercialNumberUniqueRequest, App\Http\Requests\Admin\Clients\CheckCompanyNameUniqueRequest, App\Http\Requests\Admin\Clients\CheckTaxNumberUniqueRequest
Policy registration + abilities: App\Policies\Admin\Clients\ClientPolicy registered in AuthServiceProvider for Client::class and App\Policies\Admin\Tenants\TenantPolicy registered in AuthServiceProvider for Tenant::class
Actions / Services / Gateways: App\Services\Admin\Clients\ClientService, App\Services\Shared\Tenant\TenantInitializer, App\Actions\Admin\Archive\ManageCentralArchiveAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\Client, App\Models\Admin\Tenants\Tenant, App\Models\Admin\Tenants\Domain
DataTable / index / filters / buttons: App\DataTables\Admin\Clients\ClientsDataTable (5 lifecycle stat cards, date inputs, copy/excel/mass-delete, add button, subscription status column)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/clients/create.blade.php (multi-step wizard with draft support, resource key admin.company-create)
Edit UI: resources/views/admin/clients/edit.blade.php
Review step: review-step in resources/views/admin/clients/create.blade.php
Show UI + child tables + attachments: resources/views/admin/clients/show.blade.php (profile card + subscriptions history subtable)
Archive controller / DataTable / views: App\Http\Controllers\Admin\Archive\Tenant\TenantArchiveController, App\DataTables\Admin\Archive\Tenant\TenantDataTable, resources/views/admin/archive/tenant/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Company commercial registration documents stored on secure disk
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Feature\AdminClientsFeatureTest, Tests\Unit\CentralClientsGoldenPatternTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Unit/CentralClientsGoldenPatternTest.php tests/Unit/SuperAdminGoldenPatternInventoryTest.php; php artisan test tests/Feature/AdminClientsFeatureTest.php; npm run build; php artisan view:cache
Not applicable items: None
Remaining gaps: None
Status: Compliant

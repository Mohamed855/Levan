Administration: Central Companies & Tenant Provisioning
Boundary: central
Scope / aggregate root: Tenant company accounts, tenant domain initialization, database migration dispatch, tenant status lifecycle
Routes + middleware + guard: routes/admin/admin.php (admin.companies.*) + auth:admin + permission:companies.*
Controller: App\Http\Controllers\Admin\Companies\CompanyController
FormRequests: App\Http\Requests\Admin\Companies\StoreCompanyRequest, App\Http\Requests\Admin\Companies\UpdateCompanyRequest, App\Http\Requests\Admin\Companies\CheckAdminEmailUniqueRequest, App\Http\Requests\Admin\Companies\CheckAdminPhoneUniqueRequest, App\Http\Requests\Admin\Companies\CheckCommercialNumberUniqueRequest, App\Http\Requests\Admin\Companies\CheckCompanyNameUniqueRequest, App\Http\Requests\Admin\Companies\CheckTaxNumberUniqueRequest
Policy registration + abilities: App\Policies\Admin\Tenants\TenantPolicy registered in AuthServiceProvider for Tenant::class
Actions / Services / Gateways: App\Services\Admin\Companies\CompanyService, App\Services\Shared\Tenant\TenantInitializer, App\Actions\Admin\Archive\ManageCentralArchiveAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\Tenants\Tenant, App\Models\Admin\Tenants\Domain
DataTable / index / filters / buttons: App\DataTables\Admin\Companies\CompanyDataTable (5 stat cards, copy/excel/mass-delete, add button, status switch)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/companies/create.blade.php (multi-step wizard with draft support, resource key admin.company-create)
Edit UI: resources/views/admin/companies/edit.blade.php
Review step: review-step in resources/views/admin/companies/create.blade.php
Show UI + child tables + attachments: resources/views/admin/companies/show.blade.php (profile card + subscriptions history subtable)
Archive controller / DataTable / views: App\Http\Controllers\Admin\Archive\Tenant\TenantArchiveController, App\DataTables\Admin\Archive\Tenant\TenantDataTable, resources/views/admin/archive/tenant/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Company commercial registration documents stored on secure disk
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_company_creation_update_and_archive_lifecycle"
Not applicable items: None
Remaining gaps: None
Status: Compliant

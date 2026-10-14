Administration: Tenant Business Center: Companies
Boundary: tenant
Scope / aggregate root: Business center company entities, tax information, contact records, company contracts
Routes + middleware + guard: routes/tenant/tenant.php (companies.*) + auth:tenant + companies permission
Controller: App\Http\Controllers\Tenants\BusinessCenter\Company\CompanyController, App\Http\Controllers\Tenants\Archive\Company\CompanyArchiveController
FormRequests: StoreCompanyRequest, UpdateCompanyRequest, UpdateResourceStatusRequest
Policy registration + abilities: CompanyPolicy registered in AuthServiceProvider
Actions / Services / Gateways: CompanyService (including transactional logo lifecycle); archive controller record actions
Models / Enums / Events / Jobs: Company, CompanyAttachment
DataTable / index / filters / buttons: CompaniesDataTable (5 lifecycle stat cards, copy/excel/mass-delete, add button, status switch)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/business_center/companies/create.blade.php + draft support
Edit UI: resources/views/tenants/business_center/companies/edit.blade.php
Review step: Not applicable (standard open grid form)
Show UI + child tables + attachments: resources/views/tenants/business_center/companies/show.blade.php (asymmetric two-column: company profile + contracts subtable)
Archive controller / DataTable / views: CompanyArchiveController, CompanyArchiveDataTable, resources/views/tenants/archive/companies/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Company commercial registry and logo uploads on tenant disk with rollback on failure
Tests: Tests\Feature\TenantCrudFeatureTest, Tests\Unit\TenantAssetPolicyContractTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantCrudFeatureTest.php --filter="test_companies_crud_and_archive_lifecycle" (1 passed, 27 assertions)
Not applicable items: Review step (single-stage form)
Remaining gaps: None
Status: Compliant

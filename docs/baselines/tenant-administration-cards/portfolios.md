Administration: Tenant Business Center: Portfolios
Boundary: tenant
Scope / aggregate root: Company debt and asset investment portfolios, customer account pools
Routes + middleware + guard: routes/tenant/tenant.php (portfolios.*) + auth:tenant + portfolios permission
Controller: App\Http\Controllers\Tenants\BusinessCenter\Portfolio\PortfolioController, App\Http\Controllers\Tenants\Archive\Portfolio\PortfolioArchiveController
FormRequests: StorePortfolioRequest, UpdatePortfolioRequest, UpdateResourceStatusRequest
Policy registration + abilities: PortfolioPolicy registered in AuthServiceProvider
Actions / Services / Gateways: PortfolioService; archive controller record actions
Models / Enums / Events / Jobs: Portfolio, Customer
DataTable / index / filters / buttons: PortfoliosDataTable (5 lifecycle stat cards, copy/excel/mass-delete, add button, status switch)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/business_center/portfolios/create.blade.php + draft support
Edit UI: resources/views/tenants/business_center/portfolios/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/tenants/business_center/portfolios/show.blade.php (portfolio overview + customer pool subtable)
Archive controller / DataTable / views: PortfolioArchiveController, PortfolioArchiveDataTable, resources/views/tenants/archive/portfolios/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Portfolio prospectus attachments with tenant disk management
Tests: Tests\Feature\TenantCrudFeatureTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantCrudFeatureTest.php --filter="test_portfolios_crud_and_archive_lifecycle" (1 passed, 27 assertions)
Not applicable items: Review step
Remaining gaps: None
Status: Compliant

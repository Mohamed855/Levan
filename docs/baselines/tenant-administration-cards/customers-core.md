Administration: Tenant Business Center: Customers Core
Boundary: tenant
Scope / aggregate root: Customer debtor records, contact profiles, company/portfolio association, manager-level assignment
Routes + middleware + guard: routes/tenant/tenant.php (customers.*) + auth:tenant + customers permission
Controller: App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerController, App\Http\Controllers\Tenants\Archive\Customer\CustomerArchiveController
FormRequests: StoreCustomerRequest, UpdateCustomerRequest, UpdateResourceStatusRequest
Policy registration + abilities: CustomerPolicy registered in AuthServiceProvider (preserves manager-scope check)
Actions / Services / Gateways: CustomerService; archive controller record actions
Models / Enums / Events / Jobs: Customer, Company, Portfolio
DataTable / index / filters / buttons: CustomersDataTable (5 lifecycle stat cards, copy/excel/mass-delete, add button, status switch)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/business_center/customers/create.blade.php + draft support
Edit UI: resources/views/tenants/business_center/customers/edit.blade.php
Review step: Not applicable (two-column open layout)
Show UI + child tables + attachments: resources/views/tenants/business_center/customers/show.blade.php (asymmetric layout: left profile/debt stats, right subtables: finance, notes, follows, attachments)
Archive controller / DataTable / views: CustomerArchiveController, CustomerArchiveDataTable, resources/views/tenants/archive/customers/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: National ID / KYC documents on tenant disk with signed access
Tests: Tests\Feature\TenantCrudFeatureTest, Tests\Feature\TenantDashboardComprehensiveTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantCrudFeatureTest.php --filter="test_customers_crud_and_archive_lifecycle" (1 passed, 27 assertions)
Not applicable items: Review step
Remaining gaps: None
Status: Compliant

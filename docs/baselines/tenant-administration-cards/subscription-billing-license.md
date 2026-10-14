Administration: Tenant Subscription, Billing, License & Versions
Boundary: tenant
Scope / aggregate root: Tenant commercial subscription lifecycle, invoice history, payment verification, license validation, platform version notes
Routes + middleware + guard: routes/tenant/tenant.php (subscribe.*, subscriptions.*, bills.*, licenses.*, versions.*) + auth:tenant
Controller: App\Http\Controllers\Tenants\Subscriptions\NewSubscriptionController, TenantSubscriptionController, App\Http\Controllers\Tenants\Bills\BillsController, App\Http\Controllers\Tenants\License\LicenseController, App\Http\Controllers\Tenants\Versions\VersionController
FormRequests: dedicated subscription, bill-receipt, license, and version FormRequests
Policy registration + abilities: route capabilities and typed requests; all central state is accessed through explicit gateways.
Actions / Services / Gateways: subscription and billing services, CentralSubscriptionContextGateway, CentralBillingGateway, CentralLicenseGateway, CentralVersionGateway
Models / Enums / Events / Jobs: Plan (via gateway), Bill, Subscription, License, Version
DataTable / index / filters / buttons: TenantBillsDataTable for payment history and invoices
Create UI + draft resource key + restore/purge evidence: Plan checkout wizard with receipt attachment and draft support
Edit UI: Subscription management view with change plan option
Review step: Subscription order summary and receipt verification step
Show UI + child tables + attachments: Bill invoice printable view
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: checkout.js, form-validation.js
Files and storage lifecycle: Payment receipt images stored on private disk via CentralBillingGateway
Tests: Tests\Feature\TenantDashboardPostRefactorCasesTest, Tests\Unit\TenantCentralBoundaryContractTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantDashboardPostRefactorCasesTest.php tests/Unit/TenantCentralBoundaryContractTest.php (7 passed, 430 assertions)
Not applicable items: Archive (financial records are permanent or soft-handled centrally)
Remaining gaps: None
Status: Compliant

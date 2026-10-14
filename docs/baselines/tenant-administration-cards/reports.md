Administration: Tenant Reporting Suite
Boundary: tenant
Scope / aggregate root: Business intelligence reports: Company performance, Customer debt, Customer follow-ups, Executive KPIs, Financial collections, Employee metrics
Routes + middleware + guard: routes/tenant/tenant.php (reports.{company,customer,customer-follow,executive,financial,performance}.*) + auth:tenant + reports permission
Controller: App\Http\Controllers\Tenants\Reports\Company\CompanyReportController, App\Http\Controllers\Tenants\Reports\Customer\CustomerReportController, App\Http\Controllers\Tenants\Reports\CustomerFollow\CustomerFollowReportController, App\Http\Controllers\Tenants\Reports\Executive\ExecutiveReportController, App\Http\Controllers\Tenants\Reports\Financial\FinancialReportController, App\Http\Controllers\Tenants\Reports\Performance\PerformanceReportController
FormRequests: ReportFilterRequest, ReportExportRequest
Policy registration + abilities: Route-level report permissions plus TenantExportAccess ownership checks for every generated export.
Actions / Services / Gateways: CompanyReportService, CustomerReportService, CustomerFollowReportService, FinancialReportService, CompanyPerformanceService, EmployeePerformanceService, TenantExportAccess
Models / Enums / Events / Jobs: ReportExportJob
DataTable / index / filters / buttons: Report results DataTables with advanced filters (date, company, portfolio, employee)
Create UI + draft resource key + restore/purge evidence: Not applicable (analytical query interfaces)
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Executive chart dashboard and printable summary tables
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: reports.js, date-range-picker.js, export-progress.js
Files and storage lifecycle: Generated PDF and Excel exports owned by tenant and requesting user
Tests: Tests\Unit\TenantReportExportAccessContractTest, Tests\Feature\TenantDashboardComprehensiveTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantReportExportAccessContractTest.php (3 passed, 21 assertions)
Not applicable items: Create UI, edit UI, drafts, review step, archive
Remaining gaps: None
Status: Compliant

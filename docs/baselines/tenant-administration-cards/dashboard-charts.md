Administration: Tenant Dashboard & Chart Settings
Boundary: tenant
Scope / aggregate root: Tenant KPI summary, performance widgets, chart display preferences
Routes + middleware + guard: routes/tenant.php (dashboard.*, charSetting.*) + auth:tenant
Controller: App\Http\Controllers\Tenants\dashboard\DashboardController, App\Http\Controllers\Tenants\ChartSetting\ChartSettingController
FormRequests: UpdateChartVisibilityRequest
Policy registration + abilities: dashboard.view route capability; chart visibility update is protected by its typed FormRequest.
Actions / Services / Gateways: DashboardService, UpdateChartVisibilityAction
Models / Enums / Events / Jobs: ChartSetting, TenantMetricCache
DataTable / index / filters / buttons: Not applicable (KPI widget cards and dashboard charts)
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Chart display settings modal and widget reorder interface
Review step: Not applicable
Show UI + child tables + attachments: resources/views/tenant/dashboard/index.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: chart.js integrations, apexcharts wrapper, date range picker
Files and storage lifecycle: Not applicable (data metrics only)
Tests: Tests\Feature\TenantDashboardComprehensiveTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantDashboardComprehensiveTest.php --filter="test_tenant_dashboard_and_metrics" (1 passed, 13 assertions)
Not applicable items: DataTable, create UI, drafts, review step, archive, files
Remaining gaps: None
Status: Compliant

Administration: SuperAdmin Executive Dashboard
Boundary: central
Scope / aggregate root: Executive platform metrics, tenant and user count aggregates, active subscriptions breakdown, MRR/ARR financial stats
Routes + middleware + guard: routes/admin/admin.php (admin.dashboard, admin.dashboard.platform-activity) + auth:admin + permission:dashboard.main
Controller: App\Http\Controllers\Admin\Dashboard\DashboardController
FormRequests: App\Http\Requests\Admin\Dashboard\PlatformActivityRequest
Policy registration + abilities: Not applicable (executive KPI aggregation view guarded by permission:dashboard.main middleware)
Actions / Services / Gateways: App\Services\Admin\Dashboard\DashboardService
Models / Enums / Events / Jobs: App\Models\Admin\AdminMangment\Users\AdminUser, App\Models\Admin\Tenants\Tenant, App\Models\Admin\Subscription\Subscription, App\Models\Admin\Plans\Plan, App\Models\Admin\Bills\Bill
DataTable / index / filters / buttons: Not applicable (executive metric cards, ApexCharts, and activity widgets)
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/dashboard/index.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: apexcharts.js, date-filter.js
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_dashboard_renders_successfully"
Not applicable items: Policy, DataTable, create UI, edit UI, drafts, review step, archive, files
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap

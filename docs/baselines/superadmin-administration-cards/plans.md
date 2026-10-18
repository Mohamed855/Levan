Administration: Subscription Plans & Pricing Tiers
Boundary: central
Scope / aggregate root: Central SaaS subscription plans, feature limits, billing cycles, pricing rules, plan ordering
Routes + middleware + guard: routes/admin/admin.php (admin.plans.*) + auth:admin + permission:plans.*
Controller: App\Http\Controllers\Admin\Plans\PlanController
FormRequests: App\Http\Requests\Admin\Plans\PlanRequest, App\Http\Requests\Admin\Plans\PlanReorderRequest
Policy registration + abilities: App\Policies\Admin\Plans\PlanPolicy registered in AuthServiceProvider for Plan::class
Actions / Services / Gateways: App\Services\Admin\Plans\PlanService, App\Actions\Admin\Archive\ManageCentralArchiveAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\Plans\Plan
DataTable / index / filters / buttons: App\DataTables\Admin\Plans\PlanDataTable (5 lifecycle stat cards, copy/excel/mass-delete, add button, status switch, eager-loaded subscriptions_count for row actions)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/plans/create.blade.php (multi-step wizard with draft support, resource key admin.plan-create)
Edit UI: resources/views/admin/plans/edit.blade.php
Review step: review-validation step in resources/views/admin/plans/create.blade.php (split layout with preview package card)
Show UI + child tables + attachments: resources/views/admin/plans/show.blade.php
Archive controller / DataTable / views: App\Http\Controllers\Admin\Archive\Plan\PlanArchiveController, App\DataTables\Admin\Archive\Plan\PlanDataTable, resources/views/admin/archive/plan/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPlansGoldenPatternTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Unit/CentralPlansGoldenPatternTest.php tests/Unit/SuperAdminGoldenPatternInventoryTest.php tests/Unit/DataTableDetailsControlContractTest.php; php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_plans_crud_and_archive_lifecycle|test_admin_activity_logs_and_datatables_endpoints"; npm run build; php artisan view:cache
Not applicable items: Files and storage
Remaining gaps: None
Status: Compliant

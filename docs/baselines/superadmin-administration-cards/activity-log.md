Administration: Central Audit Activity Log
Boundary: central
Scope / aggregate root: Central audit log of all administrator mutations, login history, security events, subject attribute diffs
Routes + middleware + guard: routes/admin/admin.php (admin.activity-logs.*) + auth:admin + permission:activity_log.view
Controller: App\Http\Controllers\Admin\ActivityLog\AdminActivityLogController
FormRequests: App\Http\Requests\Admin\ActivityLog\AdminActivityLogIndexRequest
Policy registration + abilities: Not applicable (read-only activity audit log guarded by permission:activity_log.view middleware)
Actions / Services / Gateways: Not applicable (read-only audit queries handled directly via controller)
Models / Enums / Events / Jobs: Spatie\Activitylog\Models\Activity
DataTable / index / filters / buttons: Server-side Yajra DataTables in AdminActivityLogController::index (filters by causer, subject type, date range, export buttons)
Create UI + draft resource key + restore/purge evidence: Not applicable (logs automatically written by Spatie Activitylog)
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/activity_logs/show.blade.php
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: date-filter.js
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_activity_logs_and_datatables_endpoints"
Not applicable items: Policy, Actions, create UI, edit UI, drafts, review step, archive, files
Remaining gaps: None
Status: Compliant

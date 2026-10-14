Administration: Tenant Tasks & My Tasks (read/export administration)
Boundary: tenant
Scope / aggregate root: Operational task queues and filtered exports; task creation and status mutation are not active routes in this administration.
Routes + middleware + guard: routes/tenant/tenant.php (tasks.*, my-tasks.*) + auth:tenant + tasks permission
Controller: App\Http\Controllers\Tenants\BusinessCenter\Task\TaskController, App\Http\Controllers\Tenants\BusinessCenter\Task\MyTaskController
FormRequests: ExportTasksRequest
Policy registration + abilities: TaskPolicy is registered in AuthServiceProvider (viewAny for tasks.view; viewOwnQueue for my_tasks.view); route middleware and ExportTasksRequest enforce the same permissions.
Actions / Services / Gateways: App\Services\Tenants\BusinessCenter\Task\TaskService
Models / Enums / Events / Jobs: Task
DataTable / index / filters / buttons: TasksDataTable and MyTasksDataTable (queue filters and export)
Create UI + draft resource key + restore/purge evidence: Not applicable: no active create route.
Edit UI: Not applicable: no active edit route.
Review step: Not applicable
Show UI + child tables + attachments: Not applicable: no active show route.
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: DataTable filters and export toolbar.
Files and storage lifecycle: Not applicable: no active task-file route.
Tests: Tests\Feature\TenantDashboardComprehensiveTest, Tests\Unit\TenantAssetPolicyContractTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantDashboardComprehensiveTest.php --filter="test_tasks_and_my_tasks" and php artisan test tests/Unit/TenantAssetPolicyContractTest.php --filter=task_queues (both passing, 8 assertions total)
Not applicable items: Create/edit/show/review/archive and file lifecycle; no active task mutations exist in routes/tenant/tenant.php.
Remaining gaps: None
Status: Compliant

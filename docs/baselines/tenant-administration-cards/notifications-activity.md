Administration: Tenant Notifications & Activity Logs
Boundary: tenant
Scope / aggregate root: User notification inbox/read tracking and tenant system activity audit logs
Routes + middleware + guard: routes/tenant/tenant.php (notifications.*, activity-logs.*) + auth:tenant
Controller: App\Http\Controllers\Tenants\Notification\NotificationController, App\Http\Controllers\Tenants\activity_log\ActivityLogController
FormRequests: MarkNotificationReadRequest, MarkAllNotificationsReadRequest, DeleteNotificationRequest, MassDeleteNotificationsRequest, ActivityLogIndexRequest, ExportActivityLogsRequest, DownloadActivityLogExportRequest
Policy registration + abilities: Notifications are always scoped through the authenticated user's notification relation; ActivityLogPolicy is registered in AuthServiceProvider (viewAny/view/delete).
Actions / Services / Gateways: ManageUserNotificationsAction, ActivityLogsExport
Models / Enums / Events / Jobs: DatabaseNotification, Activity
DataTable / index / filters / buttons: NotificationsDataTable, ActivityLogDataTable with date filters and export options
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Activity detail modal showing causer, subject, and attribute diffs
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: notification-dropdown.js, date-filter.js
Files and storage lifecycle: Exported activity log spreadsheets stored on tenant private disk with owner check
Tests: Tests\Feature\TenantDashboardComprehensiveTest
Verification commands and passing evidence: php artisan test tests/Feature/TenantDashboardComprehensiveTest.php --filter="test_activity_logs" (1 passed, 4 assertions)
Not applicable items: Create UI, edit UI, drafts, review step, archive
Remaining gaps: None
Status: Compliant

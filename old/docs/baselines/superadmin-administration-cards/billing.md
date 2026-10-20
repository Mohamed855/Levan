Administration: Central Billing & Receipts
Boundary: central
Scope / aggregate root: Central invoices, receipt auditing, payment reconciliation, manual activation
Routes + middleware + guard: routes/admin.php (admin.bills.*) + auth:admin + permission:financial_transactions.view, subscriptions.pending.*
Controller: App\Http\Controllers\Admin\Bills\AdminBillsController
FormRequests: App\Http\Requests\Admin\Bills\RejectBillRequest, App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest
Policy registration + abilities: App\Policies\Admin\Bills\BillPolicy registered in AuthServiceProvider for Bill::class
Actions / Services / Gateways: App\Services\Shared\Bills\BillService, App\Actions\Admin\Archive\ManageCentralArchiveAction
Models / Enums / Events / Jobs: App\Models\Admin\Bills\Bill
DataTable / index / filters / buttons: App\DataTables\Admin\Bill\AdminBillsDataTable, App\DataTables\Admin\Archive\Bill\BillDataTable
Create UI + draft resource key + restore/purge evidence: Not applicable (bills generated on subscription checkout)
Edit UI: Bill review and verification modal in resources/views/admin/bills/index.blade.php
Review step: Receipt verification and payment approval dialog
Show UI + child tables + attachments: resources/views/admin/bills/show.blade.php, resources/views/admin/bills/print.blade.php
Archive controller / DataTable / views: App\Http\Controllers\Admin\Archive\Bill\BillArchiveController, App\DataTables\Admin\Archive\Bill\BillDataTable, resources/views/admin/archive/bill/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Bank transfer deposit receipts stored on secure storage disk
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_subscription_bill_and_ticket_actions_with_real_records"
Not applicable items: Create UI, drafts
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap

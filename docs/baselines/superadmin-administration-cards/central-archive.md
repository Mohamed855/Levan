Administration: Central Archive for Soft-Deleted Records
Boundary: central
Scope / aggregate root: Restoration and permanent deletion of central soft-deleted models (AdminUsers, Plans, Coupons, Tenants, Bills)
Routes + middleware + guard: routes/admin/admin.php (admin.archives.*) + auth:admin + permission per model
Controller: App\Http\Controllers\Admin\Archive\AdminUser\AdminUserController, App\Http\Controllers\Admin\Archive\Plan\PlanArchiveController, App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController, App\Http\Controllers\Admin\Archive\Tenant\TenantArchiveController, App\Http\Controllers\Admin\Archive\Bill\BillArchiveController
FormRequests: App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest
Policy registration + abilities: Per-model policies restore and forceDelete abilities (AdminUserPolicy, PlanPolicy, CouponPolicy, TenantPolicy, BillPolicy)
Actions / Services / Gateways: App\Actions\Admin\Archive\ManageCentralArchiveAction
Models / Enums / Events / Jobs: App\Models\Admin\AdminMangment\Users\AdminUser, App\Models\Admin\Plans\Plan, App\Models\Admin\Coupons\Coupon, App\Models\Admin\Tenants\Tenant, App\Models\Admin\Bills\Bill
DataTable / index / filters / buttons: Dedicated Archive DataTables without header banners (App\DataTables\Admin\Archive\AdminUser\AdminUserDataTable, App\DataTables\Admin\Archive\Plan\PlanDataTable, App\DataTables\Admin\Archive\Coupon\CouponDataTable, App\DataTables\Admin\Archive\Tenant\TenantDataTable, App\DataTables\Admin\Archive\Bill\BillDataTable)
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Archive views strictly contain error alerts, filters, and table card (resources/views/admin/archive/admin_user/index.blade.php, resources/views/admin/archive/plan/index.blade.php, resources/views/admin/archive/coupon/index.blade.php, resources/views/admin/archive/tenant/index.blade.php, resources/views/admin/archive/bill/index.blade.php)
Shared components and JavaScript contracts: table-actions.js, mass-delete.js
Files and storage lifecycle: Cascade file removal on permanent force-deletion
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Unit/CentralPolicyContractTest.php
Not applicable items: Create UI, edit UI, drafts, review step, show UI
Remaining gaps: None
Status: Compliant

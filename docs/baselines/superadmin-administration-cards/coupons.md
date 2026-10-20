Administration: Promotional Discount Coupons
Boundary: central
Scope / aggregate root: Discount coupons, percentage/fixed discounts, expiration dates, usage limits, plan applicability
Routes + middleware + guard: routes/admin.php (admin.coupons.*) + auth:admin + permission:coupons.*
Controller: App\Http\Controllers\Admin\Coupons\AdminCouponController
FormRequests: App\Http\Requests\Admin\Coupons\CouponRequest
Policy registration + abilities: App\Policies\Admin\Coupons\CouponPolicy registered in AuthServiceProvider for Coupon::class
Actions / Services / Gateways: App\Services\Admin\Coupons\CouponService, App\Actions\Admin\Archive\ManageCentralArchiveAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\Coupons\Coupon
DataTable / index / filters / buttons: App\DataTables\Admin\Coupons\CouponDataTable (5 stat cards, copy/excel/mass-delete, add button, status switch)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/coupons/create.blade.php (wizard with draft support, resource key admin.coupon-create)
Edit UI: resources/views/admin/coupons/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/coupons/details.blade.php
Archive controller / DataTable / views: App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController, App\DataTables\Admin\Archive\Coupon\CouponDataTable, resources/views/admin/archive/coupon/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_coupons_crud_and_archive_lifecycle"
Not applicable items: Review step, files
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap

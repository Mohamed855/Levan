Administration: Central Administrator Users & Roles
Boundary: central
Scope / aggregate root: SuperAdmin users, Spatie admin guard roles and permissions assignment
Routes + middleware + guard: routes/admin.php (admin.users.*, admin.roles.*) + auth:admin + Spatie permissions (users.*, roles.*)
Controller: App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController, App\Http\Controllers\Admin\AdminMangment\Roles\AdminRoleController
FormRequests: App\Http\Requests\Admin\AdminMangment\Users\AdminUserStoreRequest, App\Http\Requests\Admin\AdminMangment\Users\AdminUserUpdateRequest, App\Http\Requests\Admin\AdminMangment\Users\BulkDeleteAdminUsersRequest, App\Http\Requests\Admin\AdminMangment\Users\CheckAdminUserEmailUniqueRequest, App\Http\Requests\Admin\AdminMangment\Users\CheckAdminUserNameUniqueRequest, App\Http\Requests\Admin\AdminMangment\Users\CheckAdminUserPhoneUniqueRequest, App\Http\Requests\Admin\AdminMangment\Role\AdminStoreRoleRequest, App\Http\Requests\Admin\AdminMangment\Role\AdminUpdateRoleRequest, App\Http\Requests\Admin\AdminMangment\Role\AdminUpdateUserPermissionsRequest, App\Http\Requests\Admin\AdminMangment\Role\AdminUpdateUserRoleRequest
Policy registration + abilities: App\Policies\Admin\Users\AdminUserPolicy, App\Policies\Shared\RolePolicy registered in AuthServiceProvider
Actions / Services / Gateways: App\Services\Admin\AdminMangment\Users\UserService, App\Services\Admin\AdminMangment\Role\RoleService, App\Actions\Admin\Archive\ManageCentralArchiveAction, App\Actions\Admin\MassDelete\DeleteAdminRecordsAction
Models / Enums / Events / Jobs: App\Models\Admin\AdminMangment\Users\AdminUser, Spatie\Permission\Models\Role, Spatie\Permission\Models\Permission
DataTable / index / filters / buttons: App\DataTables\Admin\AdminMangment\Users\AdminUserDataTable (5 lifecycle cards, filters, mass delete, guarded add button, status before actions), App\DataTables\Admin\AdminMangment\Roles\AdminRoleDataTable (admin-guard roles only, 5 lifecycle cards, actions collection and guarded add button)
Create UI + draft resource key + restore/purge evidence: resources/views/admin/users/create.blade.php + resources/views/admin/roles/create.blade.php + draft support
Edit UI: resources/views/admin/users/edit.blade.php, resources/views/admin/roles/edit.blade.php
Review step: Not applicable
Show UI + child tables + attachments: resources/views/admin/users/show.blade.php
Archive controller / DataTable / views: App\Http\Controllers\Admin\Archive\AdminUser\AdminUserController, App\DataTables\Admin\Archive\AdminUser\AdminUserDataTable, resources/views/admin/archive/admin_user/index.blade.php
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, mass-delete.js, x-data-table.page, x-form.controls.input, x-form.controls.select
Files and storage lifecycle: Admin avatar images stored on public disk with automatic cleanup in UserService
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralAdminUsersRolesGoldenPatternTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Unit/CentralAdminUsersRolesGoldenPatternTest.php tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_users_crud_and_archive_lifecycle|test_admin_roles_crud_lifecycle"
Not applicable items: Review step
Remaining gaps: None
Status: Compliant

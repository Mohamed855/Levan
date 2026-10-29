<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class CentralAdminUsersRolesGoldenPatternTest extends TestCase
{
    public function test_roles_index_uses_its_own_datatable_and_shared_page_structure(): void
    {
        $dataTable = file_get_contents(app_path('DataTables/Admin/AdminMangment/Roles/AdminRoleDataTable.php'));
        $index = file_get_contents(resource_path('views/admin/roles/index.blade.php'));

        self::assertStringContainsString('return Role::query()', $dataTable);
        self::assertStringContainsString("return 'roles-table'", $dataTable);
        self::assertStringContainsString("'data' => 'name'", $dataTable);
        self::assertStringContainsString('x-data-table.page', $index);
        self::assertStringContainsString("'id' => 'roles-table'", $index);
        self::assertStringNotContainsString('<table id="roles-table"', $index);
    }

    public function test_admin_users_index_has_the_five_card_golden_pattern(): void
    {
        $index = file_get_contents(resource_path('views/admin/users/index.blade.php'));

        self::assertSame(5, substr_count($index, '<x-ui.stat-card'));
        self::assertSame(5, substr_count($index, 'col="col-sm-6 col-xl"'));
    }

    public function test_user_role_assignment_remains_available_from_the_users_table(): void
    {
        $dataTable = file_get_contents(app_path('DataTables/Admin/AdminMangment/Users/AdminUserDataTable.php'));
        $index = file_get_contents(resource_path('views/admin/users/index.blade.php'));

        self::assertStringContainsString('edit-role-link', $dataTable);
        self::assertStringContainsString('editUserRoleModal', $index);
        self::assertStringContainsString('x-form.controls.select', $index);
    }

    public function test_role_assignment_is_limited_to_admin_roles_and_non_super_admin_users(): void
    {
        $request = file_get_contents(app_path('Http/Requests/Admin/AdminMangment/Role/AdminUpdateUserRoleRequest.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/AdminMangment/Roles/AdminRoleController.php'));
        $service = file_get_contents(app_path('Services/Admin/AdminMangment/Role/RoleService.php'));

        self::assertStringContainsString("where('guard_name', 'admin')", $request);
        self::assertStringContainsString('if ($user->isSuperAdmin() &&', $controller);
        self::assertStringContainsString("Role::query()->where('guard_name', 'admin')->findOrFail", $service);
    }

    public function test_role_forms_use_shared_validation_controls_and_the_create_draft_contract(): void
    {
        $formPartial = file_get_contents(resource_path('views/admin/roles/partials/_form.blade.php'));
        $create = file_get_contents(resource_path('views/admin/roles/create.blade.php')).$formPartial;
        $edit = file_get_contents(resource_path('views/admin/roles/edit.blade.php')).$formPartial;

        self::assertStringContainsString('x-form.controls.input', $create);
        self::assertStringContainsString('data-form-validate', $create);
        self::assertStringContainsString('data-draft-url', $create);
        self::assertStringContainsString('<div class="d-flex gap-2">', $create);
        self::assertStringContainsString('x-form.controls.input', $edit);
        self::assertStringContainsString('data-form-validate', $edit);
    }

    public function test_role_permissions_are_strictly_scoped_to_the_admin_guard(): void
    {
        $storeRequest = file_get_contents(app_path('Http/Requests/Admin/AdminMangment/Role/AdminStoreRoleRequest.php'));
        $updateRequest = file_get_contents(app_path('Http/Requests/Admin/AdminMangment/Role/AdminUpdateRoleRequest.php'));
        $service = file_get_contents(app_path('Services/Admin/AdminMangment/Role/RoleService.php'));

        self::assertStringContainsString("Rule::exists('permissions', 'name')->where('guard_name', 'admin')", $storeRequest);
        self::assertStringContainsString("Rule::exists('permissions', 'name')->where('guard_name', 'admin')", $updateRequest);
        self::assertStringContainsString("Permission::query()->where('guard_name', 'admin')->get()->groupBy('section')", $service);
        self::assertStringContainsString("\$role->name === 'SuperAdmin'", $service);
    }

    public function test_admin_user_and_role_views_use_their_top_level_view_and_asset_boundaries(): void
    {
        $userController = file_get_contents(app_path('Http/Controllers/Admin/AdminMangment/Users/AdminUserController.php'));
        $roleController = file_get_contents(app_path('Http/Controllers/Admin/AdminMangment/Roles/AdminRoleController.php'));
        $userIndex = file_get_contents(resource_path('views/admin/users/index.blade.php'));

        self::assertStringContainsString("'admin.users'", $userController);
        self::assertStringContainsString("'admin.roles'", $roleController);
        self::assertStringContainsString('resources/assets/js/admin/users/index.js', $userIndex);
        self::assertFileExists(resource_path('assets/js/admin/users/index.js'));
        self::assertFileExists(resource_path('assets/js/admin/roles/index.js'));
    }

    public function test_admin_and_tenant_roles_views_contain_filter_contracts(): void
    {
        $dataTable = file_get_contents(app_path('DataTables/Admin/AdminMangment/Roles/AdminRoleDataTable.php'));
        $adminIndex = file_get_contents(resource_path('views/admin/roles/index.blade.php'));
        $tenantIndex = file_get_contents(resource_path('views/tenant/user-management/roles/index.blade.php'));

        self::assertStringContainsString('applyCustomFilters', $dataTable);
        self::assertStringContainsString('usage_status', $dataTable);
        self::assertStringContainsString('date_from', $dataTable);
        self::assertStringContainsString('date_to', $dataTable);

        self::assertStringContainsString('<x-slot:filters>', $adminIndex);
        self::assertStringContainsString('<x-data-table.filters table="#roles-table">', $adminIndex);
        self::assertStringContainsString('id="filter-role"', $adminIndex);
        self::assertStringContainsString('id="filter-usage-status"', $adminIndex);
        self::assertStringContainsString('id="filter-date-from"', $adminIndex);
        self::assertStringContainsString('id="filter-date-to"', $adminIndex);

        $tenantDataTable = file_get_contents(app_path('DataTables/Tenants/UserManagement/Role/RoleDataTable.php'));
        self::assertStringContainsString('applyCustomFilters', $tenantDataTable);
        self::assertStringContainsString('usage_status', $tenantDataTable);
        self::assertStringContainsString('date_from', $tenantDataTable);
        self::assertStringContainsString('date_to', $tenantDataTable);

        self::assertStringContainsString('<x-slot:filters>', $tenantIndex);
        self::assertStringContainsString('<x-data-table.filters table="#roles-table">', $tenantIndex);
        self::assertStringContainsString('id="filter-role"', $tenantIndex);
        self::assertStringContainsString('id="filter-usage-status"', $tenantIndex);
        self::assertStringContainsString('id="filter-date-from"', $tenantIndex);
        self::assertStringContainsString('id="filter-date-to"', $tenantIndex);
    }
}

<?php

namespace App\Http\Controllers\Admin\AdminMangment\Roles;

use App\DataTables\Admin\AdminMangment\Roles\AdminRoleDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminMangment\Role\AdminStoreRoleRequest;
use App\Http\Requests\Admin\AdminMangment\Role\AdminUpdateRoleRequest;
use App\Http\Requests\Admin\AdminMangment\Role\AdminUpdateUserRoleRequest;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Services\Admin\AdminMangment\Role\RoleService;
use App\Services\Admin\AdminMangment\Users\UserService;
use App\Support\AccessLabels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    private $page = 'admin.roles';

    private $route = 'admin.roles';

    protected UserService $userService;

    protected RoleService $roleService;

    public function __construct(UserService $userService, RoleService $roleService)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->authorizeResource(Role::class, 'role');

        $this->middleware('can:roles.view')->only(['index']);
        $this->middleware('can:roles.create')->only(['create', 'store']);
        $this->middleware('can:roles.edit')->only(['edit', 'update']);
        $this->middleware('can:roles.delete')->only(['destroy']);
        $this->middleware('can:roles.assign')->only([
            'getUserRoles',
            'updateUserRoles',
        ]);
    }

    public function index(AdminRoleDataTable $dataTable)
    {
        try {
            $roles = $this->roleService->getAllRoles();
            $roleOptions = $roles->mapWithKeys(fn ($role) => [
                $role->id => AccessLabels::role($role->name),
            ])->toArray();

            return $dataTable->render($this->page.'.index', [
                'statistics' => $this->roleService->getRoleStatistics(),
                'roleOptions' => $roleOptions,
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        try {
            $permissions = $this->roleService->getGroupedPermissions();
            $sections = RoleService::ADMIN_PERMISSION_SECTIONS;

            return view($this->page.'.create', compact('permissions', 'sections'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء تحضير النموذج.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Role
    |--------------------------------------------------------------------------
    | Store a newly created role in storage.
    */
    public function store(AdminStoreRoleRequest $request): RedirectResponse
    {
        try {
            $this->roleService->createRole($request->validated());

            return redirect()->route($this->route.'.index')->with('success', 'تم إضافة الدور بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الدور');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    | Show the form for editing the specified role.
    */
    public function edit(Role $role)
    {

        try {
            $permissions = $this->roleService->getGroupedPermissions();
            $sections = RoleService::ADMIN_PERMISSION_SECTIONS;

            return view($this->page.'.edit', compact('role', 'permissions', 'sections'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء تحضير النموذج.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Role
    |--------------------------------------------------------------------------
    | Update the specified role in storage.
    */
    public function update(AdminUpdateRoleRequest $request, Role $role)
    {
        try {
            $this->roleService->updateRole($role, $request->getValidatedData());

            // return redirect()->back()->withInput()->with('success', 'تم تحديث الدور بنجاح');
            return redirect()->route($this->route.'.index')->with('success', 'تم تحديث الدور بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الدور');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Role
    |--------------------------------------------------------------------------
    | Remove the specified role from storage.
    */
    public function destroy(AdminAuthenticatedRequest $request, Role $role)
    {
        try {
            $this->roleService->deleteRole($role);

            return redirect()->route($this->route.'.index')->with('success', 'تم حذف الدور بنجاح');
        } catch (\Exception $e) {
            return redirect()->route($this->route.'.index')->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Roles
    |--------------------------------------------------------------------------
    | Get roles for a specific user.
    */
    public function getUserRoles(int $id): JsonResponse
    {
        try {
            $user = AdminUser::findOrFail($id);
            $data = $this->roleService->getUserRoleData($user);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'فشل في جلب الأدوار.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Roles
    |--------------------------------------------------------------------------
    | Update roles for a specific user.
    */
    public function updateUserRoles(AdminUpdateUserRoleRequest $request, int $id)
    {
        try {
            $user = AdminUser::findOrFail($id);

            if ($user->isSuperAdmin() && ! $user->roles()->whereKey($request->validated('role'))->exists()) {
                return response()->json(['error' => 'لا يمكن تعديل دور السوبر أدمن.'], 403);
            }

            $this->roleService->updateUserRole($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح.',
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الدور.',
            ], 500);
        }
    }
}

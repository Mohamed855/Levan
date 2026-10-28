<?php

namespace App\Http\Controllers\Tenants\UserMangment\Role;

use App\DataTables\Tenants\UserManagement\Role\RoleDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\UserManagement\Role\StoreRoleRequest;
use App\Http\Requests\Tenants\UserManagement\Role\UpdateRoleRequest;
use App\Http\Requests\Tenants\UserManagement\Role\UpdateUserRoleRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateUserStatusRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\UserManagement\Role\RoleService;
use App\Services\Tenants\UserManagement\User\UserService;
use App\Support\AccessLabels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */

    protected UserService $userService;

    protected RoleService $roleService;

    public function __construct(UserService $userService, RoleService $roleService)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->authorizeResource(Role::class, 'role');

        $this->middleware('can:roles.view')->only(['index', 'show']);
        $this->middleware('can:roles.create')->only(['create', 'store']);
        $this->middleware('can:roles.edit')->only(['edit', 'update']);
        $this->middleware('can:roles.delete')->only(['destroy']);
        $this->middleware('can:roles.assign')->only([
            'getUserRoles',
            'updateUserRoles',
            'updateStatus',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Roles List
    |--------------------------------------------------------------------------
    | Display a listing of roles with DataTables integration.
    */
    public function index(RoleDataTable $dataTable)
    {
        try {
            $roles = $this->roleService->getAllRoles();
            $roleOptions = $roles->mapWithKeys(fn ($role) => [
                $role->id => AccessLabels::role($role->name),
            ])->toArray();

            return $dataTable->render('tenant.user-management.roles.index', [
                'statistics' => $this->roleService->getRoleStatistics(),
                'roleOptions' => $roleOptions,
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    | Show the form for creating a new role.
    */
    public function create(): View|RedirectResponse
    {
        try {
            $permissions = $this->roleService->getGroupedPermissions();

            return view('tenant.user-management.roles.create', compact('permissions'));
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
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        try {
            $this->roleService->createRole($request->validated());

            return redirect()->to(tenant_route_name('roles.index'))
                ->with('success', 'تم إضافة الدور بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الدور');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Role Details
    |--------------------------------------------------------------------------
    | Display the specified role.
    */
    public function show(Role $role): View|RedirectResponse
    {
        try {
            $roleData = $this->roleService->getRoleWithDetails($role);

            return view('tenant.user-management.roles.show', compact('role', 'roleData'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء عرض الدور.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    | Show the form for editing the specified role.
    */
    public function edit(Role $role): View|RedirectResponse
    {
        try {
            $permissions = $this->roleService->getGroupedPermissions();

            return view('tenant.user-management.roles.edit', compact('role', 'permissions'));
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
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $this->roleService->updateRole($role, $request->getValidatedData());

            return redirect()->to(tenant_route_name('roles.index'))
                ->with('success', 'تم تحديث الدور بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الدور');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Role
    |--------------------------------------------------------------------------
    | Remove the specified role from storage.
    */
    public function destroy(TenantAuthenticatedRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        try {
            $this->roleService->deleteRole($role);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الدور بنجاح',
                ]);
            }

            return redirect()->to(tenant_route_name('roles.index'))
                ->with('success', 'تم حذف الدور بنجاح');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->to(tenant_route_name('roles.index'))
                ->with('error', $e->getMessage());
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
            $user = User::query()->accessible()->findOrFail($id);
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
    public function updateUserRoles(UpdateUserRoleRequest $request, int $id): JsonResponse
    {
        try {
            $user = User::query()->accessible()->findOrFail($id);
            $this->roleService->updateUserRole($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الدور.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Status
    |--------------------------------------------------------------------------
    | Update status for a specific user.
    */
    public function updateStatus(UpdateUserStatusRequest $request, int $userId): JsonResponse
    {
        try {
            $user = User::query()->accessible()->findOrFail($userId);
            $this->roleService->updateUserStatus($user, $request->validated()['status']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الحالة.',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Tenants\UserMangment\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\UserManagement\Role\StoreRoleRequest;
use App\Http\Requests\Tenants\UserManagement\Role\UpdateRoleRequest;
use App\Http\Requests\Tenants\UserManagement\Role\UpdateUserRoleRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateUserStatusRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\UserManagement\Role\RoleService;
use App\Services\Tenants\UserManagement\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

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
    | Display a listing of roles with users DataTables integration.
    */
    public function index(Request $request): View|JsonResponse
    {
        try {
            if ($request->ajax()) {
                $query = User::query()
                    ->with(['roles'])
                    ->accessible();

                if ($request->filled('status')) {
                    $query->where('status', $request->get('status'));
                }

                if ($request->filled('role')) {
                    $roleId = $request->get('role');
                    $query->whereHas('roles', function ($q) use ($roleId) {
                        $q->where('id', $roleId);
                    });
                }

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->addColumn('checkbox', function ($row) {
                        return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
                    })
                    ->addColumn('image', function (User $user) {
                        $profilePicture = $user->image
                            ? tasset($user->image)
                            : asset('static/brand/logos/logo.svg');

                        return '<img src="'.$profilePicture.'" alt="'.$user->name.'"
                             class="rounded-circle" style="width: 2.5rem; height: 2.5rem; object-fit: cover;">';
                    })
                    ->addColumn('name', function (User $user) {
                        return '<span>'.$user->name.'</span>';
                    })
                    ->addColumn('email', function (User $user) {
                        return '<a href="mailto:'.$user->email.'">'.$user->email.'</a>';
                    })
                    ->addColumn('role', function (User $user) {
                        $roleName = $user->roles->first()?->name ?? 'غير محدد';

                        return '<span class="badge bg-label-primary">'.e($roleName).'</span>';
                    })
                    ->addColumn('status', function (User $user) {
                        if (auth()->user()->can('roles.assign')) {
                            $checked = $user->status === 'active' ? 'checked' : '';
                            $url = tenant_route_name('roles.user.status', ['userId' => $user->id]);

                            return '
                                <div class="d-flex justify-content-center">
                                    <div class="form-check form-switch mb-0">
                                        <input
                                            type="checkbox"
                                            class="form-check-input js-table-status-toggle"
                                            id="status_'.$user->id.'"
                                            data-url="'.e($url).'"
                                            data-active-value="active"
                                            data-inactive-value="inactive"
                                            '.$checked.'
                                            aria-label="تبديل حالة المستخدم"
                                        >
                                    </div>
                                </div>
                            ';
                        } else {
                            $statusClass = $user->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
                            $statusText = $user->status === 'active' ? 'نشط' : 'غير نشط';

                            return '<div class="d-flex justify-content-center"><span class="badge '.$statusClass.'">'.$statusText.'</span></div>';
                        }
                    })
                    ->addColumn('created_at_formatted', function (User $user) {
                        return $user->created_at->locale('ar')->translatedFormat('d F Y');
                    })
                    ->addColumn('actions', function (User $user) {
                        if (! auth()->user()->can('roles.assign')) {
                            return '-';
                        }

                        return '<button type="button" class="btn btn-icon btn-label-primary edit-role-link" data-user-id="'.e((string) $user->id).'" aria-label="تعديل دور المستخدم"><i class="ti ti-shield-check" aria-hidden="true"></i></button>';
                    })
                    ->orderColumn('name', fn ($query, $order) => $query->orderBy('name', $order))
                    ->orderColumn('email', fn ($query, $order) => $query->orderBy('email', $order))
                    ->orderColumn('status', fn ($query, $order) => $query->orderBy('status', $order))
                    ->orderColumn('created_at', fn ($query, $order) => $query->orderBy('created_at', $order))
                    ->filterColumn('name', fn ($query, $keyword) => $query->where('name', 'like', "%{$keyword}%"))
                    ->filterColumn('email', fn ($query, $keyword) => $query->where('email', 'like', "%{$keyword}%"))
                    ->filterColumn('phone', fn ($query, $keyword) => $query->where('phone', 'like', "%{$keyword}%"))
                    ->rawColumns(['checkbox', 'image', 'name', 'email', 'role', 'status', 'actions'])
                    ->make(true);
            }

            $roles = $this->roleService->getAllRoles();
            $permissions = $this->roleService->getGroupedPermissions();

            return view('tenants.users_management.roles.index', compact('roles', 'permissions'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات. يرجى المحاولة لاحقاً.');
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

            return view('tenants.users_management.roles.create', compact('permissions'));
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

            return view('tenants.users_management.roles.show', compact('role', 'roleData'));
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

            return view('tenants.users_management.roles.edit', compact('role', 'permissions'));
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

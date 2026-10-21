<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Actions\Admin\Users\SaveAdminUserAction;
use App\DataTables\Admin\Users\AdminUserDataTable;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreAdminUserRequest;
use App\Http\Requests\Admin\Users\UpdateAdminUserRequest;
use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin_users.view')->only(['index', 'create', 'edit']);
        $this->middleware('can:admin_users.create')->only('store');
        $this->middleware('can:admin_users.update')->only(['update', 'toggle', 'destroy']);
    }

    public function index(AdminUserDataTable $dataTable)
    {
        $statistics = [
            'total' => AdminUser::count(),
            'active' => AdminUser::where('status', 'active')->count(),
            'inactive' => AdminUser::where('status', 'inactive')->count(),
            'super_admins' => AdminUser::whereHas('roles', fn ($q) => $q->where('name', 'SuperAdmin'))->count(),
            'archived' => 0,
        ];

        $roles = Role::query()->where('guard', 'admin')->orderBy('name')->get();

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
            'roles' => $roles->map(fn (Role $role): array => [
                'value' => (string) $role->id,
                'label' => $role->display_name ?: $role->name,
            ])->all(),
        ];

        return $dataTable->render('admin.users.index', [
            'totalUsers' => $statistics['total'],
            'activeUsers' => $statistics['active'],
            'inactiveUsers' => $statistics['inactive'],
            'superAdminUsers' => $statistics['super_admins'],
            'archivedUsers' => $statistics['archived'],
            'roles' => $roles,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new AdminUser(),
            'roles' => $this->roles(),
        ]);
    }

    public function edit(AdminUser $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreAdminUserRequest $request, SaveAdminUserAction $action): RedirectResponse
    {
        $user = $action->create($request->validated());
        return to_route('admin.users.index')->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    public function update(UpdateAdminUserRequest $request, AdminUser $user, SaveAdminUserAction $action): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $action->update($user, $data);
        return to_route('admin.users.index')->with('success', 'تم حفظ المستخدم بنجاح.');
    }

    public function toggle(Request $request, AdminUser $user): JsonResponse|RedirectResponse
    {
        if ($user->is(auth('admin')->user())) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكنك تعطيل حسابك الشخصي.'], 422);
            }
            return back()->with('error', 'لا يمكنك تعطيل حسابك الشخصي.');
        }

        $currentStatus = $user->status instanceof AdminUserStatus ? $user->status->value : (string) $user->status;
        $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تغيير حالة المستخدم بنجاح.',
                'status' => $newStatus,
                'is_active' => $newStatus === 'active',
            ]);
        }

        return back()->with('success', 'تم تغيير حالة المستخدم بنجاح.');
    }

    public function destroy(Request $request, AdminUser $user): JsonResponse|RedirectResponse
    {
        if ($user->is(auth('admin')->user())) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكنك حذف حسابك الشخصي.'], 422);
            }
            return back()->with('error', 'لا يمكنك حذف حسابك الشخصي.');
        }

        if ($user->isSuperAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف حساب سوبر أدمن.'], 422);
            }
            return back()->with('error', 'لا يمكن حذف حساب سوبر أدمن.');
        }

        $user->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'تم حذف المستخدم بنجاح.');
    }

    private function roles()
    {
        return Role::query()->where('guard', 'admin')->orderBy('display_name')->get();
    }
}

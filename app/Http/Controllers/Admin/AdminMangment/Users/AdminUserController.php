<?php

namespace App\Http\Controllers\Admin\AdminMangment\Users;

use App\DataTables\Admin\AdminMangment\Users\AdminUserDataTable;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminMangment\Users\AdminUserStoreRequest;
use App\Http\Requests\Admin\AdminMangment\Users\AdminUserUpdateRequest;
use App\Http\Requests\Admin\AdminMangment\Users\BulkDeleteAdminUsersRequest;
use App\Http\Requests\Admin\AdminMangment\Users\CheckAdminUserEmailUniqueRequest;
use App\Http\Requests\Admin\AdminMangment\Users\CheckAdminUserNameUniqueRequest;
use App\Http\Requests\Admin\AdminMangment\Users\CheckAdminUserPhoneUniqueRequest;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use App\Services\Admin\AdminMangment\Users\UserService;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    private $page = 'admin.users';

    private $route = 'admin.users';

    public function __construct(private UserService $userService)
    {
        $this->authorizeResource(AdminUser::class, 'user');
        $this->middleware('can:users.view')->only(['index', 'showProfile']);
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update']);
        $this->middleware('can:users.delete')->only(['destroy', 'bulkDelete']);
        $this->middleware('can:users.change_status')->only(['toggleStatus']);
        $this->middleware(function ($request, $next) {
            if (! auth()->user()?->can('users.create') && ! auth()->user()?->can('users.edit')) {
                abort(403);
            }

            return $next($request);
        })->only(['checkEmailUnique', 'checkNameUnique', 'checkPhoneUnique']);
    }

    public function index(AdminUserDataTable $dataTable)
    {
        try {
            $statistics = $this->userService->getUserStatistics();
            $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();
            $filterOptions = [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
                'roles' => $roles->map(fn (Role $role): array => [
                    'value' => $role->id,
                    'label' => $role->name,
                ]),
            ];

            return $dataTable->render($this->page.'.index', [
                'totalUsers' => $statistics['total'],
                'activeUsers' => $statistics['active'],
                'inactiveUsers' => $statistics['inactive'],
                'archivedUsers' => $statistics['archived'],
                'superAdminUsers' => $statistics['super_admins'],
                'roles' => $roles,
                'filterOptions' => $filterOptions,
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        return view($this->page.'.create');
    }

    public function store(AdminUserStoreRequest $request)
    {
        $this->userService->create($request->validated());

        return redirect()->route($this->route.'.index')->with('success', 'تم إضافة المستخدم بنجاح');
    }

    public function edit(AdminUser $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route($this->route.'.index')->with('error', 'لا يمكن تعديل بيانات السوبر أدمن');
        }

        return view($this->page.'.edit', compact('user'));
    }

    public function update(AdminUserUpdateRequest $request, AdminUser $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route($this->route.'.index')->with('error', 'لا يمكن تعديل بيانات السوبر أدمن');
        }

        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'تم تحديث المستخدم بنجاح');
    }

    public function destroy(AdminAuthenticatedRequest $request, AdminUser $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route($this->route.'.index')->with('error', 'لا يمكن حذف السوبر أدمن');
        }

        $this->userService->deleteUser($user);

        return redirect()->route('admin.users.index')->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function bulkDelete(BulkDeleteAdminUsersRequest $request)
    {
        $ids = $request->validated('ids');

        $users = AdminUser::whereIn('id', $ids)->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدمون المحددون غير موجودين',
            ], 404);
        }

        $hasSuperAdmin = $users->contains(fn (AdminUser $user) => $user->isSuperAdmin());
        if ($hasSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف السوبر أدمن',
            ], 403);
        }

        $this->userService->bulkDelete($users->pluck('id')->all());

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدمين المحددين بنجاح',
        ]);
    }

    // تغيير حالة المستخدم
    public function toggleStatus(AdminAuthenticatedRequest $request, AdminUser $user)
    {
        try {
            if ($user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تغيير حالة السوبر أدمن',
                ], 403);
            }
            $newStatus = $this->userService->toggleStatus($user);

            $statusText = $newStatus === AdminUserStatus::ACTIVE ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الحالة بنجاح',
                'status' => $newStatus->value,
                'new_status' => $newStatus,
                'status_text' => $statusText,
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة.',
            ], 500);
        }
    }

    public function showProfile($id)
    {
        $user = AdminUser::with('roles')->findOrFail($id);
        $this->authorize('view', $user);
        $role = $user->roles->first() ?? null;

        $recentActivities = Activity::where('causer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // جلب جميع الكوبونات المسندة لهذا المستخدم
        $coupons = Coupon::where('admin_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view($this->page.'.show', compact('user', 'recentActivities', 'role', 'coupons'));
    }

    public function checkEmailUnique(CheckAdminUserEmailUniqueRequest $request)
    {
        $validated = $request->validated();

        $query = AdminUser::where('email', $validated['email']);
        if (! empty($validated['user_id'])) {
            $query->where('id', '!=', $validated['user_id']);
        }

        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'البريد الإلكتروني مستخدم من قبل',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'البريد الإلكتروني متاح',
        ]);
    }

    /**
     * التحقق من تفرد الاسم
     */
    public function checkNameUnique(CheckAdminUserNameUniqueRequest $request)
    {
        $validated = $request->validated();

        $query = AdminUser::where('name', $validated['name']);
        if (! empty($validated['user_id'])) {
            $query->where('id', '!=', $validated['user_id']);
        }

        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'الاسم مستخدم من قبل',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'الاسم متاح',
        ]);
    }

    /**
     * التحقق من تفرد رقم الهاتف
     */
    public function checkPhoneUnique(CheckAdminUserPhoneUniqueRequest $request)
    {
        $validated = $request->validated();

        $query = AdminUser::where('phone', $validated['phone']);
        if (! empty($validated['user_id'])) {
            $query->where('id', '!=', $validated['user_id']);
        }

        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'رقم الهاتف مستخدم من قبل',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'رقم الهاتف متاح',
        ]);
    }
}

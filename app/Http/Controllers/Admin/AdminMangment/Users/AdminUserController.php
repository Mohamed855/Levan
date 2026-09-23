<?php

namespace App\Http\Controllers\Admin\AdminMangment\Users;

use App\DataTables\Admin\AdminMangment\Users\AdminUserDataTable;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminMangment\Users\AdminUserStoreRequest;
use App\Http\Requests\Admin\AdminMangment\Users\AdminUserUpdateRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use App\Services\Admin\AdminMangment\Users\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Model\Admin;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    private $page   = "admin.admin_mangment.users";
    private $route  = "admin.users";


    public function __construct(private UserService $userService)
    {
        $this->middleware('can:المستخدمين')->only(['index', 'showProfile']);
        $this->middleware('can:إضافة مستخدم')->only(['create', 'store']);
        $this->middleware('can:تعديل مستخدم')->only(['edit', 'update']);
        $this->middleware('can:حذف مستخدم')->only(['destroy']);
        $this->middleware('can:تغيير حالة المستخدم')->only(['toggleStatus']);
    }


    public function index(AdminUserDataTable $dataTable)
    {
        try {
            $statistics = $this->userService->getUserStatistics();
            $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();
            $totalUsers = AdminUser::withTrashed()->count();
            $activeUsers = AdminUser::where('status', AdminUserStatus::ACTIVE)->count();
            $inactiveUsers = AdminUser::where('status', AdminUserStatus::INACTIVE)->count();
            $archivedUsers = AdminUser::onlyTrashed()->count();
            return $dataTable->render($this->page . '.index', [
                'totalUsers'       => $totalUsers,
                'activeUsers'      => $activeUsers,
                'inactiveUsers'    => $inactiveUsers,
                'archivedUsers'   => $archivedUsers,
                'roles'            => $roles,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }


    public function create()
    {
        return view($this->page . '.create');
    }


    public function store(AdminUserStoreRequest $request)
    {
        $this->userService->create($request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة المستخدم بنجاح');
    }


    public function edit(AdminUser $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route($this->route . '.index')->with('error', 'لا يمكن تعديل بيانات السوبر أدمن');
        }
        return view($this->page . '.edit', compact('user'));
    }


    public function update(AdminUserUpdateRequest $request, AdminUser $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route($this->route . '.index')->with('error', 'لا يمكن تعديل بيانات السوبر أدمن');
        }

        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'تم تحديث المستخدم بنجاح');
    }


    public function destroy(AdminUser $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route($this->route . '.index')->with('error', 'لا يمكن حذف السوبر أدمن');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'تم حذف المستخدم بنجاح');
    }


    // تغيير حالة المستخدم
    public function toggleStatus(AdminUser $user)
    {
        try {
            if ($user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تغيير حالة السوبر أدمن'
                ], 403);
            }
            Log::info($user . 'user in toggle');
            $newStatus = $user->status === AdminUserStatus::ACTIVE
                ? AdminUserStatus::INACTIVE
                : AdminUserStatus::ACTIVE;

            $user->status = $newStatus;
            $user->save();

            $statusText = $newStatus === AdminUserStatus::ACTIVE ? 'نشط' : 'غير نشط';

            return response()->json([
                'success'       => true,
                'message'       => 'تم تعديل الحالة بنجاح',
                'new_status'    => $newStatus,
                'status_text'   => $statusText
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle status error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة: ' . $e->getMessage()
            ], 500);
        }
    }


   public function showProfile($id)
{
    $user = AdminUser::where('id', $id)->first();
    $role = $user->roles->first() ?? null;
    
    $recentActivities = Activity::where('causer_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

    // جلب جميع الكوبونات المسندة لهذا المستخدم
    $coupons = Coupon::where('admin_user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return view($this->page . '.show', compact('user', 'recentActivities', 'role', 'coupons'));
}

    public function checkEmailUnique(Request $request)
{
    $email = $request->input('email');
    
    $exists = AdminUser::where('email', $email)->exists();
    
    if ($exists) {
        return response()->json([
            'available' => false,
            'message' => 'البريد الإلكتروني مستخدم من قبل'
        ]);
    }
    
    return response()->json([
        'available' => true,
        'message' => 'البريد الإلكتروني متاح'
    ]);
}

/**
 * التحقق من تفرد الاسم
 */
public function checkNameUnique(Request $request)
{
    $name = $request->input('name');
    
    $exists = AdminUser::where('name', $name)->exists();
    
    if ($exists) {
        return response()->json([
            'available' => false,
            'message' => 'الاسم مستخدم من قبل'
        ]);
    }
    
    return response()->json([
        'available' => true,
        'message' => 'الاسم متاح'
    ]);
}

/**
 * التحقق من تفرد رقم الهاتف
 */
public function checkPhoneUnique(Request $request)
{
    $phone = $request->input('phone');
    
    $exists = AdminUser::where('phone', $phone)->exists();
    
    if ($exists) {
        return response()->json([
            'available' => false,
            'message' => 'رقم الهاتف مستخدم من قبل'
        ]);
    }
    
    return response()->json([
        'available' => true,
        'message' => 'رقم الهاتف متاح'
    ]);
}
}

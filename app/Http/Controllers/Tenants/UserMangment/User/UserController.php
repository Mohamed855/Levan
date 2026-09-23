<?php

namespace App\Http\Controllers\Tenants\UserMangment\User;

use App\DataTables\Tenants\UserManagement\User\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\UserManagement\User\StoreUserRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateUserRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\shared\Subscription\SubscriptionFormatter;
use App\Services\shared\Subscription\SubscriptionManager;
use App\Services\Tenants\UserManagement\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */

    public function __construct(
        private UserService $userService,
        private SubscriptionManager  $subscriptionManager,
        private SubscriptionFormatter $subscriptionFormatter
    ) {

        $this->middleware('auth');
        $this->middleware('can:المستخدمين')->only(['index']);
        $this->middleware('can:إضافة مستخدم')->only(['create', 'store']);
        $this->middleware('can:تعديل مستخدم')->only(['edit', 'update']);
        $this->middleware('can:حذف مستخدم')->only(['destroy']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Users List
    |--------------------------------------------------------------------------
    | Display a listing of users with DataTables integration and statistics.
    */
    public function index(UsersDataTable $dataTable)
    {
        $statistics = $this->userService->getUserStatistics();
        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();
        $branches = $this->userService->getActiveBranches();

        $tenant        = tenant();
        $subscription  = $this->subscriptionManager->getTenantSubscription($tenant->id);
        $formatted     = $this->subscriptionFormatter->format($subscription);

        return $dataTable->render('tenants.users_management.users.index', [
            'totalUsers' => $statistics['total'],
            'activeUsers' => $statistics['active'],
            'inactiveUsers' => $statistics['inactive'],
            'archivedUsers' => $statistics['archived'],
            'roles' => $roles,
            'branches' => $branches,
            'subscription_formatted'    => $formatted,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    | Show the form for creating a new user.
    */
    public function create(): View
    {
        $generatedEmployeeNumber = $this->userService->generateNextEmployeeNumber();
        $branches = $this->userService->getActiveBranches();

        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();

        return view('tenants.users_management.users.create', compact('branches', 'generatedEmployeeNumber', 'roles'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store New User
    |--------------------------------------------------------------------------
    | Store a newly created user in storage.
    */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $this->userService->createUser($request->validated());

            return redirect()->to(tenant_route_name('users.index'))
                ->with('success', 'تم إضافة المستخدم بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة المستخدم');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    | Show the form for editing the specified user.
    */
    public function edit(User $user): View
    {
        $user->load('roles');
        $branches = $this->userService->getActiveBranches();
        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();

        return view('tenants.users_management.users.edit', compact('user', 'branches', 'roles'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update User
    |--------------------------------------------------------------------------
    | Update the specified user in storage.
    */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->updateUser($user, $request->getValidatedData());

            return redirect()->to(tenant_route_name('users.index'))
                ->with('success', 'تم تحديث المستخدم بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث المستخدم');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Specific User Profile
    |--------------------------------------------------------------------------
    | Displays the profile page for a specific user, identified by their ID.
    | This is the core function for viewing any user's details.
    */
    public function show(User $user): View
    {
        $user->load('roles', 'branch');

        $recentActivities = Activity::where('causer_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $sessionsData = $this->userService->getUserSessionsData($user->id);

        return view('tenants.users_management.users.show', compact('user', 'recentActivities', 'sessionsData'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete User
    |--------------------------------------------------------------------------
    | Remove the specified user from storage.
    */
    public function showProfile(): View
    {
        $user = Auth::user();
        $recentActivities = Activity::where('causer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        $sessionsData = $this->userService->getUserSessionsData($user->id);


        return view('tenants.users_management.users.show', compact('user', 'recentActivities', 'sessionsData'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete User
    |--------------------------------------------------------------------------
    | Remove the specified user from storage.
    */
    public function destroy(User $user): RedirectResponse
    {
        if ((int) $user->id === 1) {
            return redirect()
                ->back()
                ->with('error', 'لا يمكن حذف المستخدم الأساسي.');
        }

        if (auth()->id() === (int) $user->id) {
            return redirect()
                ->back()
                ->with('error', 'لا يمكنك حذف حسابك الشخصي.');
        }

        try {
            $this->userService->deleteUser($user);

            return redirect()
                ->back()
                ->with('success', 'تم حذف المستخدم بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف المستخدم');
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Toggle User Status
    |--------------------------------------------------------------------------
    | Toggle user status between active and inactive.
    */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $newStatus,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Change Password Form
    |--------------------------------------------------------------------------
    | Show the form for changing user password.
    */
    public function showChangePasswordForm(): View
    {
        return view('tenants.users_management.users.reset');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    | Update the authenticated user's password.
    */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.confirmed' => 'كلمة المرور الجديدة وتأكيدها غير متطابقتين.',
            'new_password.min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        Auth::logoutOtherDevices($request->new_password);
        Auth::logout();

        return redirect()->to(tenant_route_name('login'))
            ->with('success', 'تم إعادة تعيين كلمة المرور بنجاح. يرجى تسجيل الدخول باستخدام كلمة المرور الجديدة.');
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Image
    |--------------------------------------------------------------------------
    | Update the user profile image.
    */
    public function updateImage(Request $request, User $user): JsonResponse
    {
        // التحقق من الصلاحيات
        // if (auth()->id() !== (int) $user->id && !auth()->user()->can('تعديل مستخدم')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'ليس لديك صلاحية لتعديل هذا المستخدم'
        //     ], 403);
        // }

        $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ], [
            'profile_image.required' => 'يرجى اختيار صورة',
            'profile_image.image' => 'يجب أن يكون الملف صورة',
            'profile_image.mimes' => 'الصيغ المسموح بها: JPEG, PNG, JPG, GIF',
            'profile_image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ]);

        try {
            $this->userService->updateUserImage($user, $request->file('profile_image'));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الصورة بنجاح',
                'image_url' => tasset($user->fresh()->image)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الصورة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateBackgroundImage(Request $request, User $user)
    {
        $request->validate([
            'background_image' => 'required|image|mimes:jpg,jpeg,png,gif',
        ], [
            'background_image.required' => 'يرجى تحميل صورة خلفية.',
            'background_image.image' => 'يجب أن تكون الصورة ملفًا بصيغة صورة.',
            'background_image.mimes' => 'يجب أن تكون الصورة بامتداد jpg, jpeg, png, gif.',
            'background_image.max' => 'يجب ألا يتجاوز حجم الصورة 4 ميجابايت.',
        ]);

        try {
            $this->userService->updateBackgroundImage($user, $request->file('background_image'));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الصورة بنجاح',
                'image_url' => tasset($user->fresh()->background_image)
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الصورة: ' . $e->getMessage()
            ], 500);
        }
    }
}

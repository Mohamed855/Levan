<?php

namespace App\Http\Controllers\Tenants\UserMangment\User;

use App\DataTables\Tenants\UserManagement\User\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\UserManagement\User\StoreUserRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateManagedUserStatusRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateUserRequest;
use App\Http\Requests\Tenants\Users\ChangeCurrentUserPasswordRequest;
use App\Http\Requests\Tenants\Users\UpdateUserBackgroundImageRequest;
use App\Http\Requests\Tenants\Users\UpdateUserProfileImageRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\shared\Subscription\SubscriptionFormatter;
use App\Services\shared\Subscription\SubscriptionManager;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;
use App\Services\Tenants\UserManagement\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
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
        private SubscriptionManager $subscriptionManager,
        private SubscriptionFormatter $subscriptionFormatter,
        private CentralSubscriptionContextGateway $subscriptionGateway
    ) {
        $this->authorizeResource(User::class, 'user');
        $this->middleware('auth');
        $this->middleware('can:users.view')->only(['index', 'show']);
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:users.delete')->only(['destroy']);
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
        $roles = Role::query()->where('guard_name', 'tenant')->select('id', 'name')->orderBy('name')->get();

        $tenant = tenant();
        $subscription = $this->subscriptionManager->getTenantSubscription($tenant->id);
        $formatted = $this->subscriptionFormatter->format($subscription);

        return $dataTable->render('tenant.user-management.users.index', [
            'totalUsers' => $statistics['total'],
            'activeUsers' => $statistics['active'],
            'inactiveUsers' => $statistics['inactive'],
            'archivedUsers' => $statistics['archived'],
            'roles' => $roles,
            'subscription_formatted' => $formatted,

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    | Show the form for creating a new user.
    */
    public function create(): View|RedirectResponse
    {
        $currentUsersCount = User::query()->count();
        if (! $this->subscriptionGateway->canAddUser($currentUsersCount)) {
            $limitInfo = $this->subscriptionGateway->getUserLimitInfo();
            $limit = $limitInfo['user_limit'] ?? 1;

            return redirect()->to(tenant_route_name('users.index'))
                ->with('error', "لا يمكن إضافة مستخدم جديد، لقد تم الوصول إلى الحد الأقصى للمستخدمين المسموح به في باقتك ({$limit} مستخدم).");
        }

        $generatedEmployeeNumber = $this->userService->generateNextEmployeeNumber();

        $roles = Role::query()->where('guard_name', 'tenant')->select('id', 'name')->orderBy('name')->get();

        return view('tenant.user-management.users.create', compact('generatedEmployeeNumber', 'roles'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store New User
    |--------------------------------------------------------------------------
    | Store a newly created user in storage.
    */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $currentUsersCount = User::query()->count();
        if (! $this->subscriptionGateway->canAddUser($currentUsersCount)) {
            $limitInfo = $this->subscriptionGateway->getUserLimitInfo();
            $limit = $limitInfo['user_limit'] ?? 1;

            return redirect()->to(tenant_route_name('users.index'))
                ->with('error', "لا يمكن إضافة مستخدم جديد، لقد تم الوصول إلى الحد الأقصى للمستخدمين المسموح به في باقتك ({$limit} مستخدم).");
        }

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
        $roles = Role::query()->where('guard_name', 'tenant')->select('id', 'name')->orderBy('name')->get();

        return view('tenant.user-management.users.edit', compact('user', 'roles'));
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
        $user->load('roles');

        $recentActivities = Activity::where('causer_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $sessionsData = $this->userService->getUserSessionsData($user->id);

        return view('tenant.user-management.users.show', compact('user', 'recentActivities', 'sessionsData'));
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

        return view('tenant.user-management.users.show', compact('user', 'recentActivities', 'sessionsData'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete User
    |--------------------------------------------------------------------------
    | Remove the specified user from storage.
    */
    public function destroy(TenantAuthenticatedRequest $request, User $user): JsonResponse|RedirectResponse
    {
        if ((int) $user->id === 1) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف المستخدم الأساسي.'], 422);
            }

            return redirect()
                ->back()
                ->with('error', 'لا يمكن حذف المستخدم الأساسي.');
        }

        if (auth()->id() === (int) $user->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكنك حذف حسابك الشخصي.'], 422);
            }

            return redirect()
                ->back()
                ->with('error', 'لا يمكنك حذف حسابك الشخصي.');
        }

        try {
            $this->userService->deleteUser($user);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم حذف المستخدم بنجاح');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء حذف المستخدم'], 500);
            }

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
    public function toggleStatus(UpdateManagedUserStatusRequest $request, User $user): JsonResponse
    {
        try {
            $newStatus = $request->validated('status');
            $this->userService->updateStatus($user, $newStatus);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $newStatus,
                'status_text' => $statusText,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة',
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
        return view('tenant.user-management.users.reset');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    | Update the authenticated user's password.
    */
    public function updatePassword(ChangeCurrentUserPasswordRequest $request): RedirectResponse
    {
        $user = $request->user('tenant');

        $this->userService->updatePassword($user, $request->validated('new_password'));

        Auth::guard('tenant')->logoutOtherDevices($request->validated('new_password'));
        Auth::guard('tenant')->logout();

        return redirect()->to(tenant_route_name('login'))
            ->with('success', 'تم إعادة تعيين كلمة المرور بنجاح. يرجى تسجيل الدخول باستخدام كلمة المرور الجديدة.');
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Image
    |--------------------------------------------------------------------------
    | Update the user profile image.
    */
    public function updateImage(UpdateUserProfileImageRequest $request, User $user): JsonResponse
    {
        try {
            $this->userService->updateUserImage($user, $request->file('profile_image'));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الصورة بنجاح',
                'image_url' => tasset($user->fresh()->image),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الصورة: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateBackgroundImage(UpdateUserBackgroundImageRequest $request, User $user): JsonResponse
    {

        try {
            $this->userService->updateBackgroundImage($user, $request->file('background_image'));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الصورة بنجاح',
                'image_url' => tasset($user->fresh()->background_image),
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الصورة: '.$e->getMessage(),
            ], 500);
        }
    }
}

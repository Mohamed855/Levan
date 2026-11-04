<?php

namespace App\Http\Controllers\Tenants\Archive\User;

use App\DataTables\Tenants\Archive\User\ArchivedUsersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\Http\Requests\Tenants\Archive\BulkArchiveActionRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Archive\User\UserArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class UserArchiveController extends Controller
{
    protected UserArchiveService $userArchiveService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Inject dependencies and apply authorization middleware.
    */
    public function __construct(UserArchiveService $userArchiveService)
    {
        $this->userArchiveService = $userArchiveService;
        $this->middleware('auth');
        $this->middleware('can:user_archive.view')->only(['index']);
        $this->middleware('can:user_archive.restore')->only(['restore', 'massRestore']);
        $this->middleware('can:user_archive.force_delete')->only(['forceDelete', 'massForceDelete']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    | Render archived users list with statistics.
    */
    public function index(ArchivedUsersDataTable $dataTable)
    {
        try {
            $roles = Role::select('id', 'name')
                ->orderBy('name')
                ->get();

            return $dataTable->render('tenant.archive.users.index', [
                'roles' => $roles,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Restore User
    |--------------------------------------------------------------------------
    | Restore the specified archived user.
    */
    public function restore(ArchiveItemIdRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $user = User::onlyTrashed()->findOrFail($request->validated('id'));
            $this->authorize('restore', $user);

            // Check collision with active users (ARC-10)
            if ($user->email && User::where('email', $user->email)->whereNull('deleted_at')->where('id', '!=', $user->id)->exists()) {
                $message = 'لا يمكن استرجاع المستخدم، البريد الإلكتروني مستخدم بالفعل من قبل مستخدم نشط.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message);
            }

            if ($user->phone && User::where('phone', $user->phone)->whereNull('deleted_at')->where('id', '!=', $user->id)->exists()) {
                $message = 'لا يمكن استرجاع المستخدم، رقم الهاتف مستخدم بالفعل من قبل مستخدم نشط.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message);
            }

            $gateway = app(\App\Services\Tenants\Shared\CentralSubscriptionContextGateway::class);
            if (! $gateway->canAddUser(User::query()->count())) {
                $limitInfo = $gateway->getUserLimitInfo();
                $limit = $limitInfo['user_limit'] ?? 1;
                $message = "لا يمكن استرجاع المستخدم، لقد تم الوصول إلى الحد الأقصى للمستخدمين المسموح به في باقتك ({$limit} مستخدم).";

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->back()->with('error', $message);
            }

            $this->userArchiveService->restoreUser($user);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع المستخدم بنجاح']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع المستخدم بنجاح');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية استرجاع هذا المستخدم.'], 403);
            }

            return redirect()->back()->with('error', 'ليس لديك صلاحية استرجاع هذا المستخدم.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود في الأرشيف.'], 404);
            }

            return redirect()->back()->with('error', 'المستخدم غير موجود في الأرشيف.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع المستخدم'], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء استرجاع المستخدم');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete User
    |--------------------------------------------------------------------------
    | Permanently delete the specified archived user.
    */
    public function forceDelete(ArchiveItemIdRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $user = User::onlyTrashed()->findOrFail($request->validated('id'));
            $this->authorize('forceDelete', $user);
            $this->userArchiveService->forceDeleteUser($user);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف المستخدم نهائياً']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم حذف المستخدم نهائياً');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية الحذف النهائي لهذا المستخدم.'], 403);
            }

            return redirect()->back()->with('error', 'ليس لديك صلاحية الحذف النهائي لهذا المستخدم.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود في الأرشيف.'], 404);
            }

            return redirect()->back()->with('error', 'المستخدم غير موجود في الأرشيف.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي'], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء الحذف النهائي');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Restore
    |--------------------------------------------------------------------------
    | Restore multiple archived users at once.
    */
    public function massRestore(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $userIds = $request->validated('ids');
            $users = User::onlyTrashed()->whereKey($userIds)->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على أي مستخدمين محذوفين للاسترجاع',
                ], 404);
            }

            // Authorize each user
            foreach ($users as $user) {
                $this->authorize('restore', $user);
            }

            // Check subscription limit (ARC-07)
            $gateway = app(\App\Services\Tenants\Shared\CentralSubscriptionContextGateway::class);
            $currentActiveCount = User::query()->count();
            if (! $gateway->canAddUser($currentActiveCount + $users->count() - 1)) {
                $limitInfo = $gateway->getUserLimitInfo();
                $limit = $limitInfo['user_limit'] ?? 1;

                return response()->json([
                    'success' => false,
                    'message' => "لا يمكن استرجاع المستخدمين المحددين، سيتجاوز العدد الحد الأقصى المسموح به في باقتك ({$limit} مستخدم).",
                ], 422);
            }

            // Check for collision with active users (ARC-10)
            foreach ($users as $user) {
                if ($user->email && User::where('email', $user->email)->whereNull('deleted_at')->where('id', '!=', $user->id)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => "لا يمكن استرجاع المستخدم ({$user->name})، البريد الإلكتروني مستخدم بالفعل من قبل مستخدم نشط.",
                    ], 422);
                }
                if ($user->phone && User::where('phone', $user->phone)->whereNull('deleted_at')->where('id', '!=', $user->id)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => "لا يمكن استرجاع المستخدم ({$user->name})، رقم الهاتف مستخدم بالفعل من قبل مستخدم نشط.",
                    ], 422);
                }
            }

            $restoredCount = $this->userArchiveService->massRestoreUsers($userIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} مستخدم بنجاح",
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية استرجاع أحد المستخدمين المحددين',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستعادة الجماعية',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete
    |--------------------------------------------------------------------------
    | Permanently delete multiple archived users at once.
    */
    public function massForceDelete(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $userIds = $request->validated('ids');
            $users = User::onlyTrashed()->whereKey($userIds)->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على أي مستخدمين محذوفين للحذف النهائي',
                ], 404);
            }

            // Authorize each user
            foreach ($users as $user) {
                $this->authorize('forceDelete', $user);
            }

            $deletedCount = $this->userArchiveService->massForceDeleteUsers($userIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} مستخدم نهائياً",
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الحذف النهائي لأحد المستخدمين المحددين',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي',
            ], 500);
        }
    }
}

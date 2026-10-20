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
            $this->userArchiveService->restoreUser($user);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع المستخدم بنجاح']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع المستخدم بنجاح');
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
            User::onlyTrashed()->whereKey($userIds)->get()->each(
                fn (User $user) => $this->authorize('restore', $user)
            );

            $restoredCount = $this->userArchiveService->massRestoreUsers($userIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} مستخدم بنجاح",
            ]);
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
            User::onlyTrashed()->whereKey($userIds)->get()->each(
                fn (User $user) => $this->authorize('forceDelete', $user)
            );

            $deletedCount = $this->userArchiveService->massForceDeleteUsers($userIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} مستخدم نهائياً",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Tenants\Archive\User;

use App\Http\Controllers\Controller;
use App\DataTables\Tenants\Archive\User\ArchivedUsersDataTable;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Archive\User\UserArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
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
        $this->middleware('can:أرشيف المستخدمين')->only(['index']);
        $this->middleware('can:إسترجاع السجلات')->only(['restore', 'massRestore']);
        $this->middleware('can:حذف نهائي للسجلات')->only(['forceDelete', 'massForceDelete']);
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
            $statistics = $this->userArchiveService->getArchivedUserStatistics();
            
            $roles = Role::select('id', 'name')
                ->orderBy('name')
                ->get();

            return $dataTable->render('tenants.archive.users.index', [
                'totalArchivedUsers'    => $statistics['total'],
                'archivedActiveUsers'   => $statistics['active'],
                'archivedInactiveUsers' => $statistics['inactive'],
                'recentlyArchivedUsers' => $statistics['recently_archived'],
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
    public function restore(int $userId): RedirectResponse
    {
        try {
            $user = User::onlyTrashed()->findOrFail($userId);
            $this->userArchiveService->restoreUser($user);

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع المستخدم بنجاح');
        } catch (\Exception $e) {
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
    public function forceDelete(int $userId): RedirectResponse
    {
        try {
            $user = User::onlyTrashed()->findOrFail($userId);
            $this->userArchiveService->forceDeleteUser($user);

            return redirect()
                ->back()
                ->with('success', 'تم حذف المستخدم نهائياً');
        } catch (\Exception $e) {
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
    public function massRestore(Request $request): JsonResponse
    {
        try {
            $userIds = $request->input('ids', []);
            
            if (empty($userIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي مستخدم'
                ], 400);
            }

            $restoredCount = $this->userArchiveService->massRestoreUsers($userIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} مستخدم بنجاح"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستعادة الجماعية'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete
    |--------------------------------------------------------------------------
    | Permanently delete multiple archived users at once.
    */
    public function massForceDelete(Request $request): JsonResponse
    {
        try {
            $userIds = $request->input('ids', []);
            
            if (empty($userIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي مستخدم'
                ], 400);
            }

            $deletedCount = $this->userArchiveService->massForceDeleteUsers($userIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} مستخدم نهائياً"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي'
            ], 500);
        }
    }
}
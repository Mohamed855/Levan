<?php

namespace App\Http\Controllers\Admin\Archive\AdminUser;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\AdminUser\AdminUserDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Services\Admin\AdminMangment\Users\UserService;
use App\Support\AccessLabels;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function __construct(private UserService $userService, private ManageCentralArchiveAction $archive)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:users.view')->only(['index']);
        $this->middleware('can:users.restore')->only(['restore']);
        $this->middleware('can:users.force_delete')->only(['forceDelete']);
    }

    public function index(AdminUserDataTable $dataTable)
    {
        $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
            'roles' => $roles->map(fn ($role): array => [
                'value' => $role->id,
                'label' => AccessLabels::role($role->name),
            ]),
        ];

        return $dataTable->render('admin.archive.admin_user.index', compact('roles', 'filterOptions'));
    }

    public function restore(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $adminUser = AdminUser::onlyTrashed()->findOrFail($id);
            $this->authorize('restore', $adminUser);

            if ($adminUser->isSuperAdmin()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'لا يمكن استرجاع السوبر أدمن'], 403);
                }

                return redirect()->route('admin.archives.index')
                    ->with('error', 'لا يمكن استرجاع السوبر أدمن');
            }

            // Check unique collision with active users
            if (AdminUser::where('email', $adminUser->email)->whereNull('deleted_at')->where('id', '!=', $adminUser->id)->exists()) {
                $message = 'لا يمكن استرجاع المستخدم، البريد الإلكتروني مستخدم بالفعل من قبل مستخدم نشط.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->route('admin.archives.index')->with('error', $message);
            }

            if ($adminUser->phone && AdminUser::where('phone', $adminUser->phone)->whereNull('deleted_at')->where('id', '!=', $adminUser->id)->exists()) {
                $message = 'لا يمكن استرجاع المستخدم، رقم الهاتف مستخدم بالفعل من قبل مستخدم نشط.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->route('admin.archives.index')->with('error', $message);
            }

            $this->archive->restore($adminUser);

            if ($adminUser->roles()->count() === 0) {
                $baselineRole = Role::where('guard_name', 'admin')->where('name', '!=', 'SuperAdmin')->first();
                if ($baselineRole) {
                    $adminUser->assignRole($baselineRole);
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع المستخدم بنجاح.']);
            }

            return redirect()->route('admin.archives.index')
                ->with('success', 'تم استرجاع المستخدم بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية استرجاع هذا المستخدم.'], 403);
            }

            return redirect()->route('admin.archives.index')->with('error', 'ليس لديك صلاحية استرجاع هذا المستخدم.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.index')->with('error', 'المستخدم غير موجود في الأرشيف.');
        } catch (\Exception $e) {
            Log::error('Error restoring admin user: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع المستخدم.'], 500);
            }

            return redirect()->route('admin.archives.index')
                ->with('error', 'حدث خطأ أثناء استرجاع المستخدم.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $adminUser = AdminUser::onlyTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $adminUser);

            if ($adminUser->isSuperAdmin()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'لا يمكن حذف السوبر أدمن'], 403);
                }

                return redirect()->route('admin.archives.index')
                    ->with('error', 'لا يمكن حذف السوبر أدمن');
            }

            $this->userService->forceDeleteUser($adminUser);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف المستخدم نهائيًا بنجاح.']);
            }

            return redirect()->route('admin.archives.index')
                ->with('success', 'تم حذف المستخدم نهائيًا بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية الحذف النهائي لهذا المستخدم.'], 403);
            }

            return redirect()->route('admin.archives.index')->with('error', 'ليس لديك صلاحية الحذف النهائي لهذا المستخدم.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.index')->with('error', 'المستخدم غير موجود في الأرشيف.');
        } catch (\Exception $e) {
            Log::error('Error force deleting admin user: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي للمستخدم.'], 500);
            }

            return redirect()->route('admin.archives.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للمستخدم.');
        }
    }
}

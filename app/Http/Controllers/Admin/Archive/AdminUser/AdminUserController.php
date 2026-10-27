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
            $adminUser = AdminUser::withTrashed()->findOrFail($id);
            $this->authorize('restore', $adminUser);

            if ($adminUser->isSuperAdmin()) {
                return redirect()->route('admin.archives.index')
                    ->with('error', 'لا يمكن استرجاع السوبر أدمن');
            }

            $this->archive->restore($adminUser);

            return redirect()->route('admin.archives.index')
                ->with('success', 'تم استرجاع المستخدم بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring admin user: '.$e->getMessage());

            return redirect()->route('admin.archives.index')
                ->with('error', 'حدث خطأ أثناء استرجاع المستخدم.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $adminUser = AdminUser::withTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $adminUser);

            if ($adminUser->isSuperAdmin()) {
                return redirect()->route('admin.archives.index')
                    ->with('error', 'لا يمكن حذف السوبر أدمن');
            }

            $this->userService->forceDeleteUser($adminUser);

            return redirect()->route('admin.archives.index')
                ->with('success', 'تم حذف المستخدم نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting admin user: '.$e->getMessage());

            return redirect()->route('admin.archives.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للمستخدم.');
        }
    }
}

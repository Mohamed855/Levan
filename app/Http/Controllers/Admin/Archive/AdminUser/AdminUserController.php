<?php
namespace App\Http\Controllers\Admin\Archive\AdminUser;

use App\DataTables\Admin\Archive\AdminUser\AdminUserDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Model\Admin;

class AdminUserController extends Controller
{
    public function index(AdminUserDataTable $dataTable)
    {
        return $dataTable->render('admin.archive.admin_user.index');
    }

    public function restore(int $id)
    {
        try {
             $adminUser = AdminUser::withTrashed()->findOrFail($id);
            $adminUser->restore();

            return redirect()->route('admin.archives.index')
                ->with('success', 'تم استرجاع المستخدم بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring admin user: ' . $e->getMessage());

            return redirect()->route('admin.archives.index')
                ->with('error', 'حدث خطأ أثناء استرجاع المستخدم .');
        }
    }

    public function forceDelete(int $id)
    {
        try {
            $adminUser = AdminUser::withTrashed()->findOrFail($id);
            $adminUser->forceDelete();

            return redirect()->route('admin.archives.index')
                ->with('success', 'تم حذف المستخدم  نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting admin user: ' . $e->getMessage());

            return redirect()->route('admin.archives.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للمستخدم .');
        }
    }
}
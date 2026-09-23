<?php
namespace App\Http\Controllers\Admin\Archive\Tenant;
use App\DataTables\Admin\Archive\Tenant\TenantDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\Log;

class TenantArchiveController extends Controller
{
    public function index(TenantDataTable $dataTable)
    {
        return $dataTable->render('admin.archive.tenant.index');
    }
    public function restore($id)
    {
        try {
             $tenant = Tenant::withTrashed()->findOrFail($id);
            $tenant->restore();

            return redirect()->route('admin.archives.tenants.index')
                ->with('success', 'تم استرجاع العميل بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring tenant: ' . $e->getMessage());

            return redirect()->route('admin.archives.teantns.index')
                ->with('error', 'حدث خطأ أثناء استرجاع العميل .');
        }
    }
    public function forceDelete($id)
    {
        try {
            $tenant = Tenant::withTrashed()->findOrFail($id);
            $tenant->forceDelete();

            return redirect()->route('admin.archives.tenants.index')
                ->with('success', 'تم حذف العميل نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting tenant: ' . $e->getMessage());

            return redirect()->route('admin.archives.tenants.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للعميل .');
        }
    }
}
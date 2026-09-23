<?php
namespace App\Http\Controllers\Admin\Archive\Plan;
use App\DataTables\Admin\Archive\Plan\PlanDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Plans\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanArchiveController extends Controller
{
    public function index(PlanDataTable $dataTable)
    {
        return $dataTable->render('admin.archive.plan.index');
    }
    public function restore(int $id)
    {
        try {
             $plan = Plan::withTrashed()->findOrFail($id);
            $plan->restore();

            return redirect()->route('admin.archives.plans.index')
                ->with('success', 'تم استرجاع الباقة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring plan: ' . $e->getMessage());

            return redirect()->route('admin.archives.plans.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الباقة .');
        }
    }
    
    public function forceDelete(int $id)
    {
        try {
            $plan = Plan::withTrashed()->findOrFail($id);
            $plan->forceDelete();

            return redirect()->route('admin.archives.plans.index')
                ->with('success', 'تم حذف الباقة نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting plan: ' . $e->getMessage());

            return redirect()->route('admin.archives.plans.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للباقة .');
        }
    }
}
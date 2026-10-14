<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Archive\Plan;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Plan\PlanDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\Log;

class PlanArchiveController extends Controller
{
    public function __construct(private ManageCentralArchiveAction $archive)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:plans.view')->only(['index']);
        $this->middleware('can:plans.restore')->only(['restore']);
        $this->middleware('can:plans.force_delete')->only(['forceDelete']);
    }

    public function index(PlanDataTable $dataTable)
    {
        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
            'domains' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
        ];

        return $dataTable->render('admin.archive.plan.index', compact('filterOptions'));
    }

    public function restore(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $plan = Plan::withTrashed()->findOrFail($id);
            $this->authorize('restore', $plan);
            $this->archive->restore($plan);

            return redirect()->route('admin.archives.plans.index')
                ->with('success', 'تم استرجاع الباقة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring plan: '.$e->getMessage());

            return redirect()->route('admin.archives.plans.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الباقة.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $plan = Plan::withTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $plan);

            if ($plan->subscriptions()->exists()) {
                return redirect()->route('admin.archives.plans.index')
                    ->with('error', __('plans.errors.cannot_force_delete_used_plan'));
            }

            $this->archive->forceDelete($plan);

            return redirect()->route('admin.archives.plans.index')
                ->with('success', 'تم حذف الباقة نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting plan: '.$e->getMessage());

            return redirect()->route('admin.archives.plans.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للباقة.');
        }
    }
}

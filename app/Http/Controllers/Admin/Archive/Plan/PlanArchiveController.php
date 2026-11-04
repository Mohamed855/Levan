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
            $plan = Plan::onlyTrashed()->findOrFail($id);
            $this->authorize('restore', $plan);
            $this->archive->restore($plan);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع الباقة بنجاح.']);
            }

            return redirect()->route('admin.archives.plans.index')
                ->with('success', 'تم استرجاع الباقة بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية استرجاع هذه الباقة.'], 403);
            }

            return redirect()->route('admin.archives.plans.index')->with('error', 'ليس لديك صلاحية استرجاع هذه الباقة.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الباقة غير موجودة في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.plans.index')->with('error', 'الباقة غير موجودة في الأرشيف.');
        } catch (\Exception $e) {
            Log::error('Error restoring plan: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع الباقة.'], 500);
            }

            return redirect()->route('admin.archives.plans.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الباقة.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $plan = Plan::onlyTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $plan);

            if ($plan->subscriptions()->exists()) {
                $errorMsg = __('plans.errors.cannot_force_delete_used_plan');
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }

                return redirect()->route('admin.archives.plans.index')
                    ->with('error', $errorMsg);
            }

            $this->archive->forceDelete($plan);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الباقة نهائيًا بنجاح.']);
            }

            return redirect()->route('admin.archives.plans.index')
                ->with('success', 'تم حذف الباقة نهائيًا بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية الحذف النهائي لهذه الباقة.'], 403);
            }

            return redirect()->route('admin.archives.plans.index')->with('error', 'ليس لديك صلاحية الحذف النهائي لهذه الباقة.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الباقة غير موجودة في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.plans.index')->with('error', 'الباقة غير موجودة في الأرشيف.');
        } catch (\Exception $e) {
            Log::error('Error force deleting plan: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي للباقة.'], 500);
            }

            return redirect()->route('admin.archives.plans.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للباقة.');
        }
    }
}

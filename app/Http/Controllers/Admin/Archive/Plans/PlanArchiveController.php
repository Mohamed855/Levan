<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Archive\Plans;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Plans\PlanArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Plans\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PlanArchiveController extends Controller
{
    public function __construct(private readonly ManageCentralArchiveAction $archive)
    {
        $this->middleware('can:plans.view')->only('index');
        $this->middleware('can:plans.update')->only(['restore', 'forceDelete']);
    }

    public function index(PlanArchiveDataTable $dataTable)
    {
        return $dataTable->render('admin.archives.plans.index');
    }

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $plan = Plan::onlyTrashed()->findOrFail($id);
        $this->archive->restore($plan);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع الباقة بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع الباقة بنجاح.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $plan = Plan::onlyTrashed()->findOrFail($id);

        if (method_exists($plan, 'subscriptions') && $plan->subscriptions()->exists()) {
            $msg = 'لا يمكن الحذف النهائي للباقة لوجود اشتراكات مرتبطة بها.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $this->archive->forceDelete($plan);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الباقة نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف الباقة نهائياً بنجاح.');
    }
}

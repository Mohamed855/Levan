<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Archive\Assignments;

use App\Actions\Tenant\Archive\ManageTenantArchiveAction;
use App\DataTables\Tenant\Archive\Assignments\AssignmentArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Assignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AssignmentArchiveController extends Controller
{
    public function __construct(private readonly ManageTenantArchiveAction $archive)
    {
        $this->middleware('can:archives.view')->only('index');
        $this->middleware('can:archives.restore')->only('restore');
        $this->middleware('can:archives.force_delete')->only('forceDelete');
    }

    public function index(AssignmentArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenant.archives.assignments.index');
    }

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $assignment = Assignment::onlyTrashed()->findOrFail($id);
        $this->archive->restore($assignment);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع الإسناد بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع الإسناد بنجاح.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $assignment = Assignment::onlyTrashed()->findOrFail($id);

        if ($assignment->properties()->exists() || $assignment->auctions()->exists()) {
            $msg = 'لا يمكن الحذف النهائي للإسناد لوجود عقارات أو مزادات تابعة له.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $this->archive->forceDelete($assignment);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الإسناد نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف الإسناد نهائياً بنجاح.');
    }
}

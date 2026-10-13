<?php

namespace App\Http\Controllers\Tenants\Archive\Assignment;

use App\Actions\Tenants\Assignments\ForceDeleteAssignmentAction;
use App\Actions\Tenants\Assignments\RestoreAssignmentAction;
use App\DataTables\Tenants\Assignments\AssignmentArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\Models\Tenants\Assignments\Assignment;

class AssignmentArchiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:assignments_archive.view')->only(['index']);
        $this->middleware('can:assignments_archive.restore')->only(['restore']);
        $this->middleware('can:assignments_archive.force_delete')->only(['forceDelete']);
    }

    public function index(AssignmentArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenants.archive.assignments.index');
    }

    public function restore(ArchiveItemIdRequest $request, RestoreAssignmentAction $action)
    {
        $assignment = Assignment::onlyTrashed()->findOrFail($request->validated('id'));
        $this->authorize('restore', $assignment);

        try {
            $action->execute($assignment);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استعادة الإسناد بنجاح']);
            }

            return redirect()->back()->with('success', 'تم استعادة الإسناد بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استعادة الإسناد'], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء استعادة الإسناد');
        }
    }

    public function forceDelete(ArchiveItemIdRequest $request, ForceDeleteAssignmentAction $action)
    {
        $assignment = Assignment::onlyTrashed()->findOrFail($request->validated('id'));
        $this->authorize('forceDelete', $assignment);

        try {
            $action->execute($assignment);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الإسناد نهائياً بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم حذف الإسناد نهائياً بنجاح');
        } catch (\LogicException $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الإسناد نهائياً',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الإسناد نهائياً');
        }
    }
}

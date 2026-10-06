<?php

namespace App\Http\Controllers\Tenants\Archive\Assignment;

use App\DataTables\Tenants\Assignments\AssignmentArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\Assignments\Assignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

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
        return $dataTable->render('tenants.archive.assignments.index', [
            'totalArchived' => Assignment::onlyTrashed()->count(),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $assignment = Assignment::onlyTrashed()->findOrFail($id);
            $assignment->restore();

            return redirect()->back()->with('success', 'تم استعادة الإسناد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء استعادة الإسناد');
        }
    }

    public function forceDelete(int $id): JsonResponse
    {
        try {
            $assignment = Assignment::onlyTrashed()->findOrFail($id);
            $assignment->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الإسناد نهائياً بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الإسناد نهائياً',
            ], 500);
        }
    }
}

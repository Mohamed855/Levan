<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Archive\Bills;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Bills\BillArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BillArchiveController extends Controller
{
    public function __construct(private readonly ManageCentralArchiveAction $archive)
    {
        $this->middleware('can:bills.view')->only('index');
        $this->middleware('can:bills.update')->only(['restore', 'forceDelete']);
    }

    public function index(BillArchiveDataTable $dataTable)
    {
        return $dataTable->render('admin.archives.bills.index');
    }

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $bill = Bill::onlyTrashed()->findOrFail($id);
        $this->archive->restore($bill);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع الفاتورة بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع الفاتورة بنجاح.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $bill = Bill::onlyTrashed()->findOrFail($id);
        $this->archive->forceDelete($bill);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفاتورة نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف الفاتورة نهائياً بنجاح.');
    }
}

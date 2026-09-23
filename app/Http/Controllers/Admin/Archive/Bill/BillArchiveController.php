<?php
namespace App\Http\Controllers\Admin\Archive\Bill;

use App\DataTables\Admin\Archive\Bill\BillDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Bills\Bill;

class BillArchiveController extends Controller
{
    public function index(BillDataTable $dataTable)
    {
        return $dataTable->render('admin.archive.bill.index');
    }
    public function restore($id)
    {
        $bill = Bill::withTrashed()->findOrFail($id);
        $bill->restore();

        return redirect()->route('admin.archives.bills.index')->with('success', 'تم استعادة الفاتورة بنجاح.');
    }
    public function forceDelete($id)
    {
        $bill = Bill::withTrashed()->findOrFail($id);
        $bill->forceDelete();

        return redirect()->route('admin.archives.bills.index')->with('success', 'تم حذف الفاتورة نهائيًا بنجاح.');
    }
}
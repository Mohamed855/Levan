<?php

namespace App\Http\Controllers\Admin\Archive\Bill;

use App\DataTables\Admin\Archive\Bill\BillDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\Bills\Bill;

class BillArchiveController extends Controller
{
    public function index(BillDataTable $dataTable)
    {
        $filterOptions = [
            'types' => [
                ['value' => 'subscription', 'label' => 'اشتراك'],
                ['value' => 'renewal', 'label' => 'تجديد'],
                ['value' => 'extra_users', 'label' => 'مستخدمين إضافيين'],
                ['value' => 'domain', 'label' => 'دومين'],
            ],
            'statuses' => [
                ['value' => 'paid', 'label' => 'مدفوعة'],
                ['value' => 'unpaid', 'label' => 'غير مدفوعة'],
                ['value' => 'partially_paid', 'label' => 'مدفوعة جزئياً'],
                ['value' => 'cancelled', 'label' => 'ملغاة'],
            ],
            'paymentMethods' => [
                ['value' => 'bank_transfer', 'label' => 'تحويل بنكي'],
                ['value' => 'credit_card', 'label' => 'بطاقة ائتمانية'],
                ['value' => 'cash', 'label' => 'نقدي'],
            ],
        ];

        return $dataTable->render('admin.archive.bill.index', compact('filterOptions'));
    }

    public function restore(AdminAuthenticatedRequest $request, $id)
    {
        $bill = Bill::withTrashed()->findOrFail($id);
        $bill->restore();

        return redirect()->route('admin.archives.bills.index')->with('success', 'تم استعادة الفاتورة بنجاح.');
    }

    public function forceDelete(AdminAuthenticatedRequest $request, $id)
    {
        $bill = Bill::withTrashed()->findOrFail($id);
        $bill->forceDelete();

        return redirect()->route('admin.archives.bills.index')->with('success', 'تم حذف الفاتورة نهائيًا بنجاح.');
    }
}

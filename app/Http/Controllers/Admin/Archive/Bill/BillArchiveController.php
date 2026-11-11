<?php

namespace App\Http\Controllers\Admin\Archive\Bill;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Bill\BillDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\Bills\Bill;

class BillArchiveController extends Controller
{
    public function __construct(private ManageCentralArchiveAction $archive)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:financial_transactions.view')->only('index');
        $this->middleware('can:financial_transactions.restore')->only('restore');
        $this->middleware('can:financial_transactions.force_delete')->only('forceDelete');
    }

    public function index(BillDataTable $dataTable)
    {
        $filterOptions = [
            'types' => [
                ['value' => 'subscription', 'label' => 'اشتراك'],
                ['value' => 'renewal', 'label' => 'تجديد'],
                ['value' => 'extra_users', 'label' => 'مستخدمين إضافيين'],
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
        try {
            $bill = Bill::onlyTrashed()->findOrFail($id);
            $this->authorize('restore', $bill);
            $this->archive->restore($bill);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استعادة الفاتورة بنجاح.']);
            }

            return redirect()->route('admin.archives.bills.index')->with('success', 'تم استعادة الفاتورة بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية استعادة هذه الفاتورة.'], 403);
            }

            return redirect()->route('admin.archives.bills.index')->with('error', 'ليس لديك صلاحية استعادة هذه الفاتورة.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الفاتورة غير موجودة في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.bills.index')->with('error', 'الفاتورة غير موجودة في الأرشيف.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استعادة الفاتورة.'], 500);
            }

            return redirect()->route('admin.archives.bills.index')->with('error', 'حدث خطأ أثناء استعادة الفاتورة.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, $id)
    {
        try {
            $bill = Bill::onlyTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $bill);
            $this->archive->forceDelete($bill);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الفاتورة نهائيًا بنجاح.']);
            }

            return redirect()->route('admin.archives.bills.index')->with('success', 'تم حذف الفاتورة نهائيًا بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية الحذف النهائي لهذه الفاتورة.'], 403);
            }

            return redirect()->route('admin.archives.bills.index')->with('error', 'ليس لديك صلاحية الحذف النهائي لهذه الفاتورة.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الفاتورة غير موجودة في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.bills.index')->with('error', 'الفاتورة غير موجودة في الأرشيف.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي للفاتورة.'], 500);
            }

            return redirect()->route('admin.archives.bills.index')->with('error', 'حدث خطأ أثناء الحذف النهائي للفاتورة.');
        }
    }
}

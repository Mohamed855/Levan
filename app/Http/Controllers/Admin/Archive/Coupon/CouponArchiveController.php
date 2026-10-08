<?php

namespace App\Http\Controllers\Admin\Archive\Coupon;

use App\DataTables\Admin\Archive\Coupon\CouponDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\Coupons\Coupon;
use Illuminate\Support\Facades\Log;

class CouponArchiveController extends Controller
{
    public function index(CouponDataTable $dataTable)
    {
        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
            'types' => [
                ['value' => 'fixed', 'label' => 'مبلغ ثابت'],
                ['value' => 'percentage', 'label' => 'نسبة مئوية'],
            ],
        ];

        return $dataTable->render('admin.archive.coupon.index', compact('filterOptions'));
    }
    public function restore(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $plan = Coupon::withTrashed()->findOrFail($id);
            $plan->restore();

            return redirect()->route('admin.archives.coupons.index')
                ->with('success', 'تم استرجاع الكوبون بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring plan: ' . $e->getMessage());

            return redirect()->route('admin.archives.coupons.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الكوبون .');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $plan = Coupon::withTrashed()->findOrFail($id);
            $plan->forceDelete();

            return redirect()->route('admin.archives.coupons.index')
                ->with('success', 'تم حذف الكوبون نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting plan: ' . $e->getMessage());

            return redirect()->route('admin.archives.coupons.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للباقة .');
        }
    }
}

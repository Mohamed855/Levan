<?php

namespace App\Http\Controllers\Admin\Archive\Coupon;

use App\DataTables\Admin\Archive\Coupon\CouponDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Coupons\Coupon;
use Illuminate\Support\Facades\Log;

class CouponArchiveController extends Controller
{
    public function index(CouponDataTable $dataTable)
    {
        return $dataTable->render('admin.archive.coupon.index');
    }
    public function restore(int $id)
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

    public function forceDelete(int $id)
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

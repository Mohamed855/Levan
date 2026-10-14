<?php

namespace App\Http\Controllers\Admin\Archive\Coupon;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Coupon\CouponDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\Coupons\Coupon;
use Illuminate\Support\Facades\Log;

class CouponArchiveController extends Controller
{
    public function __construct(private ManageCentralArchiveAction $archive)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:coupons.view')->only('index');
        $this->middleware('can:coupons.delete')->only(['restore', 'forceDelete']);
    }

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
            $coupon = Coupon::withTrashed()->findOrFail($id);
            $this->authorize('restore', $coupon);
            $this->archive->restore($coupon);

            return redirect()->route('admin.archives.coupons.index')
                ->with('success', 'تم استرجاع الكوبون بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error restoring plan: '.$e->getMessage());

            return redirect()->route('admin.archives.coupons.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الكوبون .');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $coupon = Coupon::withTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $coupon);
            $this->archive->forceDelete($coupon);

            return redirect()->route('admin.archives.coupons.index')
                ->with('success', 'تم حذف الكوبون نهائيًا بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error force deleting plan: '.$e->getMessage());

            return redirect()->route('admin.archives.coupons.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للباقة .');
        }
    }
}

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
        $this->middleware('can:coupons.restore')->only('restore');
        $this->middleware('can:coupons.force_delete')->only('forceDelete');
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
            $coupon = Coupon::onlyTrashed()->findOrFail($id);
            $this->authorize('restore', $coupon);
            $this->archive->restore($coupon);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع الكوبون بنجاح.']);
            }

            return redirect()->route('admin.archives.coupons.index')
                ->with('success', 'تم استرجاع الكوبون بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية استرجاع هذا الكوبون.'], 403);
            }

            return redirect()->route('admin.archives.coupons.index')->with('error', 'ليس لديك صلاحية استرجاع هذا الكوبون.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الكوبون غير موجود في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.coupons.index')->with('error', 'الكوبون غير موجود في الأرشيف.');
        } catch (\Exception $e) {
            Log::error('Error restoring coupon: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع الكوبون.'], 500);
            }

            return redirect()->route('admin.archives.coupons.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الكوبون.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, int $id)
    {
        try {
            $coupon = Coupon::onlyTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $coupon);
            $this->archive->forceDelete($coupon);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الكوبون نهائيًا بنجاح.']);
            }

            return redirect()->route('admin.archives.coupons.index')
                ->with('success', 'تم حذف الكوبون نهائيًا بنجاح.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية الحذف النهائي لهذا الكوبون.'], 403);
            }

            return redirect()->route('admin.archives.coupons.index')->with('error', 'ليس لديك صلاحية الحذف النهائي لهذا الكوبون.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الكوبون غير موجود في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.coupons.index')->with('error', 'الكوبون غير موجود في الأرشيف.');
        } catch (\Exception $e) {
            Log::error('Error force deleting coupon: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي للكوبون.'], 500);
            }

            return redirect()->route('admin.archives.coupons.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للكوبون.');
        }
    }
}

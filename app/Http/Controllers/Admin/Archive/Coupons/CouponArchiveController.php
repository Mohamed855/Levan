<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Archive\Coupons;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Coupons\CouponArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CouponArchiveController extends Controller
{
    public function __construct(private readonly ManageCentralArchiveAction $archive)
    {
        $this->middleware('can:coupons.view')->only('index');
        $this->middleware('can:coupons.update')->only(['restore', 'forceDelete']);
    }

    public function index(CouponArchiveDataTable $dataTable)
    {
        return $dataTable->render('admin.archives.coupons.index');
    }

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $coupon = Coupon::onlyTrashed()->findOrFail($id);
        $this->archive->restore($coupon);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع الكوبون بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع الكوبون بنجاح.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $coupon = Coupon::onlyTrashed()->findOrFail($id);
        $this->archive->forceDelete($coupon);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الكوبون نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف الكوبون نهائياً بنجاح.');
    }
}

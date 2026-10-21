<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Coupons;

use App\Actions\Admin\Coupons\SaveCouponAction;
use App\DataTables\Admin\Coupons\CouponDataTable;
use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupons\StoreCouponRequest;
use App\Http\Requests\Admin\Coupons\UpdateCouponRequest;
use App\Models\Admin\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:coupons.view')->only(['index', 'create', 'edit']);
        $this->middleware('can:coupons.create')->only('store');
        $this->middleware('can:coupons.update')->only(['update', 'toggle']);
        $this->middleware('can:coupons.delete')->only('destroy');
    }

    public function index(CouponDataTable $dataTable): mixed
    {
        $totalCoupons = Coupon::query()->count();
        $activeCoupons = Coupon::query()->where('is_active', true)->count();
        $inactiveCoupons = Coupon::query()->where('is_active', false)->count();
        $expiredCoupons = Coupon::query()->where('expires_at', '<', now())->count();
        $usedCount = (int) Coupon::query()->sum('used_count');

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
            'types' => array_map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], CouponDiscountType::cases()),
        ];

        return $dataTable->render('admin.coupons.index', compact(
            'totalCoupons',
            'activeCoupons',
            'inactiveCoupons',
            'expiredCoupons',
            'usedCount',
            'filterOptions'
        ));
    }

    public function create(): View
    {
        return view('admin.coupons.create', ['coupon' => new Coupon]);
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function store(StoreCouponRequest $request, SaveCouponAction $action): RedirectResponse
    {
        $coupon = $action->create($request->validated(), $request->user('admin'));

        return to_route('admin.coupons.edit', $coupon)->with('success', 'تم إنشاء الكوبون بنجاح.');
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, SaveCouponAction $action): RedirectResponse
    {
        $action->update($coupon, $request->validated());

        return to_route('admin.coupons.index')->with('success', 'تم حفظ الكوبون.');
    }

    public function toggle(Coupon $coupon): mixed
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $coupon->is_active,
                'message' => 'تم تحديث حالة الكوبون بنجاح.',
            ]);
        }

        return back()->with('success', 'تم تحديث حالة الكوبون.');
    }

    public function destroy(Coupon $coupon): mixed
    {
        $coupon->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الكوبون بنجاح.']);
        }

        return to_route('admin.coupons.index')->with('success', 'تم حذف الكوبون بنجاح.');
    }
}

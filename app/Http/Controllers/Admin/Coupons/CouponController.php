<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Coupons;

use App\Actions\Admin\Coupons\SaveCouponAction;
use App\DataTables\Admin\Coupons\CouponDataTable;
use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupons\StoreCouponRequest;
use App\Http\Requests\Admin\Coupons\UpdateCouponRequest;
use App\Http\Requests\Admin\Coupons\UpdateCouponStatusRequest;
use App\Models\Admin\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:coupons.view')->only(['index', 'create', 'edit', 'show']);
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
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create', ['coupon' => new Coupon]);
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.edit', compact('coupon'));
    }

    public function show(Coupon $coupon): View
    {
        $this->authorize('view', $coupon);

        /** @var LengthAwarePaginator $redemptions */
        $redemptions = $coupon->redemptions()
            ->with(['bill.tenant'])
            ->latest('id')
            ->paginate(20);

        return view('admin.coupons.show', compact('coupon', 'redemptions'));
    }

    public function store(StoreCouponRequest $request, SaveCouponAction $action): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $coupon = $action->create($request->validated(), $request->user('admin'));

        return to_route('admin.coupons.edit', $coupon)->with('success', 'تم إنشاء الكوبون بنجاح.');
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, SaveCouponAction $action): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $action->update($coupon, $request->validated());

        return to_route('admin.coupons.index')->with('success', 'تم حفظ الكوبون.');
    }

    public function toggle(UpdateCouponStatusRequest $request, Coupon $coupon): mixed
    {
        $this->authorize('update', $coupon);
        $coupon->update(['is_active' => $request->boolean('is_active')]);

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
        $this->authorize('delete', $coupon);

        $coupon->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الكوبون بنجاح.']);
        }

        return to_route('admin.coupons.index')->with('success', 'تم حذف الكوبون بنجاح.');
    }
}

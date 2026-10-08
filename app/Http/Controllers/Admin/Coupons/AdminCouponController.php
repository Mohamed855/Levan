<?php

namespace App\Http\Controllers\Admin\Coupons;

use App\DataTables\Admin\Coupons\CouponDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Coupons\CouponRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Coupons\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminCouponController extends Controller
{
    private $page   = "admin.coupons";
    private $route  = "admin.coupons";


    public function __construct(private CouponService $couponService)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:coupons.view')->only(['index']);
        $this->middleware('can:coupons.create')->only(['create', 'store']);
        $this->middleware('can:coupons.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:coupons.delete')->only(['destroy']);
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            $requestedStatus = null;
            if (request()->has('status')) {
                $requestedStatus = request()->boolean('status');
            } elseif (request()->has('is_active')) {
                $requestedStatus = request()->boolean('is_active');
            }

            $coupon = $this->couponService->toggleActive($coupon, $requestedStatus);

            return response()->json([
                'success'     => true,
                'message'     => $coupon->is_active ? 'تم تفعيل الكوبون بنجاح' : 'تم تعطيل الكوبون بنجاح',
                'is_active'   => (bool) $coupon->is_active,
                'status'      => (bool) $coupon->is_active,
                'status_text' => $coupon->is_active ? 'نشط' : 'غير نشط',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'تعذر تحديث حالة الكوبون.',
            ], 500);
        }
    }


    public function index(CouponDataTable $dataTable)
    {
        try {
            $statistics = $this->couponService->getCouponStatistics();
            $coupon = Bill::WhereNotNull('tenant_id')->whereNotNull('coupon_id')->get()->count();
            // $tenant_id = Bill::WhereNotNull('tenant_id')->whereNotNull('coupon_id')->get()->value('tenant_id');

            // $coupon_details = Coupon::where('id', $coupon->id)->first();
            // $tenant = Tenant::where('id', $tenant_id)->first();
            $filterOptions = [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
                'types' => [
                    ['value' => 'percentage', 'label' => 'نسبة مئوية'],
                    ['value' => 'fixed', 'label' => 'نسبة رقمية'],
                ],
            ];

            return $dataTable->render($this->page . '.index', [
                'totalPlans'       => $statistics['total'],
                'activecoupons'      => $statistics['active'],
                'inactivecoupons'    => $statistics['inactive'],
                'couponCount' => $coupon,
                'filterOptions' => $filterOptions,
                // 'tenantUsedCouponCount' => $tenant->count()

            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }


    public function create()
    {
        $admin_users = AdminUser::get();
        $generatedCode = $this->couponService->generateUniqueCode();
        return view($this->page . '.create', compact('admin_users', 'generatedCode'));
    }


    public function store(CouponRequest $request)
    {
        $this->couponService->create($request->validated());
        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الكوبون بنجاح');
    }


    public function edit(Coupon $coupon)
    {
        $admin_users = AdminUser::get();
        return view($this->page . '.edit', compact('coupon', 'admin_users'));
    }


    public function update(CouponRequest $request, Coupon $coupon)
    {
        $this->couponService->update($coupon, $request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث الكوبون بنجاح');
    }


    public function destroy(AdminAuthenticatedRequest $request, Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route($this->route . '.index')->with('success', 'تم حذف الكوبون بنجاح');
    }

    public function getCouponDetails(Coupon $coupon)
    {
        try {
            $couponDetails = $coupon->load('admin_user');

            // Get all tenants who used this coupon through bills
            $tenants = Tenant::whereHas('bills', function ($query) use ($coupon) {
                $query->where('coupon_id', $coupon->id);
            })->with(['bills' => function ($query) use ($coupon) {
                $query->where('coupon_id', $coupon->id)
                    ->orderBy('created_at', 'desc');
            }])->get();

            return view('admin.coupons.details', compact('couponDetails', 'tenants'));
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الكوبون',
            ], 500);
        }
    }
}

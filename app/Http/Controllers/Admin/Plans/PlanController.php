<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plans;

use App\DataTables\Admin\Plans\PlanDataTable;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Plans\PlanReorderRequest;
use App\Http\Requests\Admin\Plans\PlanRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\Admin\Plans\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    private string $page = 'admin.plans';

    private string $route = 'admin.plans';

    public function __construct(private readonly PlanService $planService)
    {
        $this->middleware('auth:admin');
        $this->authorizeResource(Plan::class, 'plan');
        $this->middleware('can:plans.view')->only(['index', 'show']);
        $this->middleware('can:plans.create')->only(['create', 'store']);
        $this->middleware('can:plans.edit')->only(['edit', 'update', 'toggleActive']);
        $this->middleware('can:plans.delete')->only(['destroy']);
        $this->middleware('can:plans.reorder')->only(['updateSortOrder']);
    }

    public function index(PlanDataTable $dataTable)
    {
        try {
            $statistics = $this->planService->getPlanStatistics();

            $filterOptions = [
                'statuses' => [
                    ['value' => 'active',   'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
                'plan_types' => array_map(fn ($case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], PlanType::cases()),
                'billing_cycles' => array_map(fn ($case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], SubscriptionBillingCycle::cases()),
                'limits_modes' => array_map(fn ($case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], PlanLimitsMode::cases()),
                'availability' => [
                    ['value' => 'available', 'label' => 'متاحة الآن'],
                    ['value' => 'future',    'label' => 'مجدولة مستقبلاً'],
                ],
                'discounts' => [
                    ['value' => 'with_discount',    'label' => 'تحتوي على خصم'],
                    ['value' => 'without_discount', 'label' => 'بدون خصم'],
                ],
                'domains' => [
                    ['value' => 'active',   'label' => 'مفعلة'],
                    ['value' => 'inactive', 'label' => 'غير مفعلة'],
                ],
                'most_requested' => [
                    ['value' => 'yes', 'label' => 'الأكثر طلباً فقط'],
                ],
            ];

            return $dataTable->render($this->page.'.index', [
                'totalPlans' => $statistics['total'],
                'activePlans' => $statistics['active'],
                'inactivePlans' => $statistics['inactive'],
                'activeSubscriptions' => $statistics['active_subscriptions'],
                'expiredSubscriptions' => $statistics['expired_subscriptions'],
                'monthlyRevenue' => $statistics['monthly_revenue'],
                'filterOptions' => $filterOptions,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب بيانات الباقات.');
        }
    }

    public function show(Plan $plan): View|RedirectResponse
    {
        try {
            $data = $this->planService->getPlanDetails($plan);

            return view($this->page.'.show', [
                'plan' => $data['plan'],
                'subscriptions' => $data['subscriptions'],
                'statistics' => $data['statistics'],
                'mostRequestedPlanId' => $data['mostRequestedPlanId'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->route.'.index')->with('error', 'حدث خطأ أثناء جلب تفاصيل الباقة.');
        }
    }

    public function create(): View
    {
        return view($this->page.'.create');
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $this->planService->create($request->validated());

        return redirect()->route($this->route.'.index')->with('success', __('plans.success.created'));
    }

    public function edit(Plan $plan): View
    {
        $hasSubscriptions = $this->planService->hasSubscriptions($plan);

        return view($this->page.'.edit', compact('plan', 'hasSubscriptions'));
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $this->planService->update($plan, $request->validated());

        return redirect()->route($this->route.'.index')->with('success', __('plans.success.updated'));
    }

    public function destroy(AdminAuthenticatedRequest $request, Plan $plan): RedirectResponse
    {
        try {
            $this->planService->delete($plan);

            return redirect()->route($this->route.'.index')->with('success', __('plans.success.deleted'));
        } catch (\DomainException $e) {
            return redirect()->route($this->route.'.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($this->route.'.index')->with('error', 'حدث خطأ غير متوقع أثناء حذف الباقة.');
        }
    }

    public function toggleActive(AdminAuthenticatedRequest $request, Plan $plan): JsonResponse
    {
        try {
            $plan = $this->planService->toggleActive($plan);

            return response()->json([
                'success' => true,
                'message' => $plan->is_active ? 'تم تفعيل الباقة بنجاح' : 'تم تعطيل الباقة بنجاح',
                'is_active' => $plan->is_active,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'تعذر تحديث حالة الباقة',
            ], 500);
        }
    }

    public function updateSortOrder(PlanReorderRequest $request): JsonResponse
    {
        try {
            $order = $request->validated('order');

            foreach ($order as $item) {
                Plan::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }

            $this->planService->clearMostRequestedCache();

            return response()->json([
                'success' => true,
                'message' => __('plans.success.reordered'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الترتيب',
            ], 500);
        }
    }
}

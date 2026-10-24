<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin\Plans;
use App\Actions\Admin\Plans\SavePlanAction;
use App\Actions\Admin\Plans\ReorderPlansAction;
use App\DataTables\Admin\Plans\PlanDataTable;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plans\StorePlanRequest;
use App\Http\Requests\Admin\Plans\UpdatePlanRequest;
use App\Http\Requests\Admin\Plans\UpdatePlanStatusRequest;
use App\Http\Requests\Admin\Plans\ReorderPlansRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:plans.view')->only(['index', 'show']);
        $this->middleware('can:plans.create')->only(['create', 'store']);
        $this->middleware('can:plans.update')->only(['edit', 'update', 'toggleStatus', 'reorderForm', 'reorder']);
        $this->middleware('can:plans.delete')->only('destroy');
    }

    public function index(PlanDataTable $dataTable): mixed
    {
        $statistics = [
            'total' => Plan::count(),
            'active' => Plan::where('is_active', true)->count(),
            'inactive' => Plan::where('is_active', false)->count(),
            'available' => Plan::publiclyAvailable()->count(),
            'future' => Plan::where('available_from', '>', now())->count(),
        ];

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
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
                ['value' => 'future', 'label' => 'مجدولة مستقبلاً'],
            ],
            'discounts' => [
                ['value' => 'with_discount', 'label' => 'تحتوي على خصم'],
                ['value' => 'without_discount', 'label' => 'بدون خصم'],
            ],
            'domains' => [
                ['value' => 'active', 'label' => 'مفعلة'],
                ['value' => 'inactive', 'label' => 'غير مفعلة'],
            ],
        ];

        return $dataTable->render('admin.plans.index', [
            'totalPlans' => $statistics['total'],
            'activePlans' => $statistics['active'],
            'inactivePlans' => $statistics['inactive'],
            'availablePlans' => $statistics['available'],
            'futurePlans' => $statistics['future'],
            'filterOptions' => $filterOptions,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Plan::class);

        return view('admin.plans.create', ['plan' => new Plan]);
    }

    public function store(StorePlanRequest $request, SavePlanAction $action, WizardDraftService $drafts): RedirectResponse
    {
        $this->authorize('create', Plan::class);

        $plan = $action->create($request->validated());
        $drafts->discard($request, 'admin.plans.create');

        return to_route('admin.plans.show', $plan)->with('success', 'تم إنشاء الباقة بنجاح.');
    }

    public function show(Plan $plan): View
    {
        $this->authorize('view', $plan);

        return view('admin.plans.show', compact('plan'));
    }

    public function edit(Plan $plan): View
    {
        $this->authorize('update', $plan);

        return view('admin.plans.edit', compact('plan'));
    }

    public function reorderForm(): View
    {
        return view('admin.plans.reorder', ['plans' => Plan::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function reorder(ReorderPlansRequest $request, ReorderPlansAction $action): RedirectResponse
    {
        $action->execute($request->validated('order'));

        return to_route('admin.plans.index')->with('success', 'تم حفظ ترتيب الباقات.');
    }

    public function update(UpdatePlanRequest $request, Plan $plan, SavePlanAction $action): RedirectResponse
    {
        $this->authorize('update', $plan);

        $action->update($plan, $request->validated());

        return to_route('admin.plans.show', $plan)->with('success', 'تم حفظ التعديلات.');
    }

    public function toggleStatus(UpdatePlanStatusRequest $request, Plan $plan): mixed
    {
        $this->authorize('update', $plan);
        $plan->update(['is_active' => $request->boolean('is_active')]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $plan->is_active,
                'message' => 'تم تحديث حالة الباقة بنجاح.',
            ]);
        }

        return back()->with('success', 'تم تحديث حالة الباقة.');
    }

    public function destroy(Plan $plan): mixed
    {
        $this->authorize('delete', $plan);

        if ($plan->subscriptions()->exists()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف باقة مرتبطة باشتراكات، استخدم زر التعطيل بدلاً من ذلك.'], 422);
            }

            return back()->with('error', 'لا يمكن حذف باقة مرتبطة باشتراكات.');
        }

        $plan->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الباقة بنجاح.']);
        }

        return to_route('admin.plans.index')->with('success', 'تم حذف الباقة بنجاح.');
    }
}

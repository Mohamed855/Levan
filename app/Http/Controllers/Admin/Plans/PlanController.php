<?php

namespace App\Http\Controllers\Admin\Plans;

use App\DataTables\Admin\Plans\PlanDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plans\PlanRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\Admin\Plans\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    private $page   = "admin.plans";
    private $route  = "admin.plans";


    public function __construct(private PlanService $planService)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:plans.view')->only(['index']);
        $this->middleware('can:plans.create')->only(['create', 'store']);
        $this->middleware('can:plans.edit')->only(['edit', 'update']);
        $this->middleware('can:plans.delete')->only(['destroy']);
    }


    public function index(PlanDataTable $dataTable)
    {
        try {
            $statistics = $this->planService->getPlanStatistics();

            return $dataTable->render($this->page . '.index', [
                'totalPlans'       => $statistics['total'],
                'activePlans'      => $statistics['active'],
                'inactivePlans'    => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }


    public function create()
    {
        return view($this->page . '.create');
    }


    public function store(PlanRequest $request)
    {
        $this->planService->create($request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الباقة بنجاح');
    }


    public function edit(Plan $plan)
    {
        return view($this->page . '.edit', compact('plan'));
    }


    public function update(PlanRequest $request, Plan $plan)
    {
        $this->planService->update($plan, $request->validated());

        return redirect()->route('admin.plans.index')->with('success', 'تم تحديث الباقة بنجاح');
    }


    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'تم حذف الباقة بنجاح');
    }


    public function updateSortOrder(Request $request)
    {
        try {
            $order = $request->input('order');

            foreach ($order as $item) {
                Plan::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث ترتيب الباقات بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الترتيب'
            ], 500);
        }
    }
}

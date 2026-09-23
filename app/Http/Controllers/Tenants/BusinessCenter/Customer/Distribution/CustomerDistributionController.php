<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Distribution;

use App\DataTables\Tenants\BusinessCenter\Customer\Distribution\CustomerDistributionDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Distribution\CreateDistributionRequest;
use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\BusinessCenter\Customer\Distribution\CustomerDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerDistributionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    */
    protected CustomerDistributionService $distributionService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(CustomerDistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
        $this->middleware('auth');
        $this->middleware('can:توزيع العملاء')->only(['index', 'show']);
        $this->middleware('can:إضافة توزيع')->only(['create', 'execute']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Distribution Main Page With DataTable
    |--------------------------------------------------------------------------
    */
    public function index(CustomerDistributionDataTable $dataTable)
    {
        try {
            $statistics = $this->distributionService->getSimpleDistributionStatistics();


            return $dataTable->render('tenants.business_center.customers.distribution.index', [
                'totalCustomers' => $statistics['total_customers'],
                'unassignedCustomers' => $statistics['unassigned_customers'],
                'totalCollectors' => $statistics['total_collectors'],
                'totalDistributions' => $statistics['total_distributions'],
                'thisMonthDistributions' => $statistics['this_month_distributions'],
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Distribution Form
    |--------------------------------------------------------------------------
    */
    public function create(): View
    {
        $formData = $this->distributionService->getCompaniesAndPortfoliosWithUnassignedCustomers();
         
        return view('tenants.business_center.customers.distribution.create', [
            'companies' => $formData['companies'],
            'portfolios' => $formData['portfolios'],
            'collectors' => User::role('collector')->select('id', 'name')->orderBy('name')->get(),
            'branches' =>  Branch::pluck('name','id')->all(),
            'teams' => Team::pluck('name','id')->all(),
            'distributionMethods' => $this->distributionService->getDistributionMethods(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Distribution Batch Details
    |--------------------------------------------------------------------------
    */
    public function show(string $batchId): View
    {
        try {
            $batchDetails = $this->distributionService->getDistributionBatchDetails($batchId);

            if (empty($batchDetails)) {
                abort(404, 'لم يتم العثور على عملية التوزيع المطلوبة.');
            }

            $allCustomerIds = collect($batchDetails['collectors_details'] ?? [])
                ->flatMap(function ($detail) {
                    if (isset($detail['customer_ids'])) {
                        return json_decode($detail['customer_ids'], true) ?? [];
                    }
                    return [];
                })
                ->unique()
                ->toArray();

            $customers = collect();
            if (!empty($allCustomerIds)) {
                $customers = Customer::whereIn('id', $allCustomerIds)
                    ->with(['company:id,name'])
                    ->get();
            }

            return view('tenants.business_center.customers.distribution.show', [
                'batchDetails' => $batchDetails,
                'customers' => $customers,
            ]);
        } catch (\Exception $e) {
            return redirect()->tenant_route_name('customers.distribution.index')
                ->with('error', 'حدث خطأ غير متوقع أثناء عرض التفاصيل.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Distribution Results
    |--------------------------------------------------------------------------
    */
    public function preview(CreateDistributionRequest $request): JsonResponse
    {
        try {
            $result = $this->distributionService->previewDistribution($request->validated());

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Distribution
    |--------------------------------------------------------------------------
    | Execute Distribution Process .
    */
    public function execute(CreateDistributionRequest $request): RedirectResponse
    {
        try {
            $result = $this->distributionService->executeDistribution($request->validated());

            $message = "تم توزيع {$result['distributed_customers']} عميل على {$result['assigned_collectors']} محصل بنجاح";

            if ($request->has('apply_targets') && $request->boolean('apply_targets')) {
                $calculatorSettings = [
                    'monthly_target' => $request->input('calculator_monthly_target'),
                    'employees_count' => $request->input('calculator_employees_count'),
                    'duration' => $request->input('calculator_duration'),
                ];

                if (
                    $calculatorSettings['monthly_target'] > 0 &&
                    $calculatorSettings['employees_count'] > 0 &&
                    $calculatorSettings['duration'] > 0
                ) {

                    $targetResult = $this->distributionService->createTargetsFromCalculator(
                        $result['collector_ids'],
                        $calculatorSettings,
                        $result['batch_id']
                    );

                    if ($targetResult['created'] > 0) {
                        $message .= " وتم إنشاء {$targetResult['created']} هدف";
                    }
                }
            }

            return redirect()->tenant_route_name('customers.distribution.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get Available Customers For Distribution
    |--------------------------------------------------------------------------
    */
    public function getAvailableCustomers(Request $request): JsonResponse
    {
        // $filters = $request->only(['company_id', 'portfolio_id', 'customer_status_id', 'has_debt_only']);

        try {
            $customers = $this->distributionService->getFilteredCustomersForDistribution($filters);

            return response()->json([
                'success' => true,
                'data' => $customers,
                'summary' => [
                    'total_customers' => $customers->count(),
                    'total_debt' => $customers->sum('debt_amount'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collectors For API
    |--------------------------------------------------------------------------
    */
    public function getCollectors(): JsonResponse
    {
        try {
            $collectors = User::role('collector')->select('id', 'name')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $collectors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل المحصلين'
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get Portfolios with Unassigned Customers by Company
    |--------------------------------------------------------------------------
    */
    public function getPortfoliosWithUnassignedCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $company_id = $request->company_id;
        $portfolio_id = $request->input('portfolio_id');
        $getTotal = $request->boolean('get_total', false);

        if ($getTotal && $portfolio_id) {
            $totalDebt = Customer::where('portfolio_id', $portfolio_id)
                ->where('company_id', $company_id)
                ->whereNull('relationship_manager_id')
                ->get()
                ->sum('remaining_debt');

            return response()->json([
                'success' => true,
                'total_debt' => $totalDebt
            ]);
        }

        $portfolios = Portfolio::where('company_id', $company_id)
            ->whereHas('customers', function ($query) {
                $query->whereNull('relationship_manager_id');
            })
            ->where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'portfolios' => $portfolios
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Targets
    |--------------------------------------------------------------------------
    | Calculate targets preview .
    */
    public function calculateTargets(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'distribution_result' => 'required|array',
                'duration' => 'required|integer|min:1|max:12',
                'start_month' => 'required|integer|min:1|max:12',
                'start_year' => 'required|integer|min:2024'
            ]);

            $monthlyTargets = $this->distributionService->calculateMonthlyTargets(
                $request->input('distribution_result'),
                $request->input('duration'),
                $request->input('start_month'),
                $request->input('start_year')
            );
            return response()->json([
                'success' => true,
                'data' => $monthlyTargets
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    // في ملف: CustomerDistributionService.php
    // استبدل دالة calculateMonthlyTargets بهذه النسخة:

    public function calculateMonthlyTargets(array $distributionResult, int $duration, int $startMonth, int $startYear): array
    {
        $monthlyTargets = [];

        foreach ($distributionResult as $collectorId => $data) {
            try {
                $totalAmount = floatval($data['total_debt']);
                $monthlyAmount = floor($totalAmount / $duration);
                $remainder = $totalAmount - ($monthlyAmount * $duration);

                // التعامل مع collector كـ array أو object
                $collectorName = is_array($data['collector'])
                    ? $data['collector']['name']
                    : $data['collector']->name;

                $months = [];
                for ($i = 0; $i < $duration; $i++) {
                    $currentMonth = (($startMonth - 1 + $i) % 12) + 1;
                    $currentYear = $startYear + floor(($startMonth - 1 + $i) / 12);

                    $amount = $monthlyAmount;
                    if ($i >= $duration - $remainder) {
                        $amount += 1;
                    }

                    $months[] = [
                        'month' => $currentMonth,
                        'year' => $currentYear,
                        'amount' => $amount,
                        'month_name' => $this->getMonthName($currentMonth)
                    ];
                }

                $monthlyTargets[$collectorId] = [
                    'collector_id' => $collectorId,
                    'collector_name' => $collectorName,
                    'total_amount' => $totalAmount,
                    'monthly_amount' => $monthlyAmount,
                    'months' => $months
                ];
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $monthlyTargets;
    }

   public function getTeamsByBranch(Request $request)
{
    $branchId = $request->branch_id;
    
    if (!$branchId) {
        return response()->json([
            'success' => false,
            'message' => 'Branch ID is required'
        ], 400);
    }
    
    // جلب المجموعات من المستخدمين الموجودين في هذا الفرع
    $teams = Team::whereHas('branch', function($query) use ($branchId) {
        $query->where('branch_id', $branchId);
    })
    ->select('id', 'name')
    ->orderBy('name')
    ->get();
    
    return response()->json([
        'success' => true,
        'teams' => $teams
    ]);
}

public function getCollectorsByTeam(Request $request)
{
    $teamIds = $request->team_ids; // array of team IDs
    $branchId = $request->branch_id;
    
    if (!$teamIds || !is_array($teamIds)) {
        return response()->json([
            'success' => false,
            'message' => 'Team IDs are required'
        ], 400);
    }
    
    // جلب المحصلين بناءً على الفرع والمجموعات
    $collectors = User::
         where('branch_id', $branchId)
        ->whereIn('team_id', $teamIds)
        ->select('id', 'name', 'branch_id', 'team_id')
        ->orderBy('name')
        ->get();
    
    return response()->json([
        'success' => true,
        'collectors' => $collectors
    ]);
}

}

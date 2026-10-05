<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Distribution;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Customer Distribution Service
 * Handles all customer distribution operations with advanced algorithms.
 */
class CustomerDistributionService
{
    /*
    |--------------------------------------------------------------------------
    | Distribution Methods Constants
    |-------------------------------------------------------
    -------------------
    */
    const METHOD_BALANCED_AMOUNT = 'balanced_amount';
    const METHOD_BALANCED_COUNT = 'balanced_count';
    const METHOD_HYBRID_BALANCED = 'hybrid_balanced';

    /*
    |--------------------------------------------------------------------------
    | Get Simple Distribution Statistics For Main Page
    |--------------------------------------------------------------------------
    */
    public function getSimpleDistributionStatistics(): array
    {
        $totalCustomers = Customer::count();
        $unassignedCustomers = Customer::whereNull('relationship_manager_id')->count();
        $totalCollectors = User::role('collector')->count();

        $totalDistributions = CustomerDistribution::count();
        $thisMonthDistributions = CustomerDistribution::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total_customers' => $totalCustomers,
            'unassigned_customers' => $unassignedCustomers,
            'total_collectors' => $totalCollectors,
            'total_distributions' => $totalDistributions,
            'this_month_distributions' => $thisMonthDistributions,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Form Data For Create Distribution
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        return [
            'companies' => Company::active()->select('id', 'name')->orderBy('name')->get(),
            'portfolios' => Portfolio::active()->select('id', 'name', 'company_id')->with('company:id,name')->orderBy('name')->get(),
            'collectors' => User::role('collector')->select('id', 'name')->orderBy('name')->get(),
            // 'customer_statuses' => SettingsCustomerStatus::select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Distribution Methods
    |--------------------------------------------------------------------------
    */
    public function getDistributionMethods(): array
    {
        return [
            self::METHOD_BALANCED_AMOUNT => [
                'name' => 'التوزيع المتوازن حسب المبالغ',
                'description' => 'توزيع العملاء بحيث تكون المبالغ متقاربة بين المحصلين',
                'icon' => 'ti-coin',
                'recommended' => false,
            ],
            self::METHOD_BALANCED_COUNT => [
                'name' => 'التوزيع المتوازن حسب العدد',
                'description' => 'توزيع العملاء بحيث يكون عدد العملاء متقارب بين المحصلين',
                'icon' => 'ti-users',
                'recommended' => false,
            ],
            self::METHOD_HYBRID_BALANCED => [
                'name' => 'التوزيع المتوازن المختلط',
                'description' => 'توزيع العملاء بحيث يكون العدد والمبلغ متوازنين معاً بين المحصلين',
                'icon' => 'ti-coin',
                'recommended' => true,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Distribution Method Label
    |--------------------------------------------------------------------------
    */
    public function getDistributionMethodLabel(string $method): string
    {
        return $this->getDistributionMethods()[$method]['name'] ?? 'غير محدد';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Filtered Customers For Distribution
    |--------------------------------------------------------------------------
    */
    public function getFilteredCustomersForDistribution(array $filters): Collection
    {
        $query = Customer::unassigned()->with('transactions');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (!empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }


        // if (!empty($filters['customer_status_id'])) {
        //     $query->where('customer_status_id', $filters['customer_status_id']);
        // }

        $customers = $query->get()->map(function ($customer) {
            $customer->debt_amount = $customer->remaining_debt;
            return $customer;
        });

        // if (!empty($filters['has_debt_only'])) {
        //     $customers = $customers->where('debt_amount', '>', 0);
        // }

        return $customers->sortByDesc('debt_amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Distribution
    |--------------------------------------------------------------------------
    */
    public function previewDistribution(array $data): array
    {
        $customers = $this->getCustomersForDistribution($data);
        $collectors = $this->getSelectedCollectors($data['collector_ids']);

        if ($customers->isEmpty()) {
            throw new \Exception('لا يوجد عملاء متاحين للتوزيع');
        }
        if ($collectors->isEmpty()) {
            throw new \Exception('يجب اختيار محصل واحد على الأقل بصلاحية (Collector)');
        }

        $distributionResult = $this->applyDistributionAlgorithm($customers, $collectors, $data['distribution_method']);

        return [
            'total_customers' => $customers->count(),
            'total_debt' => $customers->sum('debt_amount'),
            'collectors_count' => $collectors->count(),
            'distribution' => $distributionResult,
            'method' => $data['distribution_method'],
            'method_label' => $this->getDistributionMethodLabel($data['distribution_method']),
            'statistics' => $this->calculateDistributionStatistics($distributionResult),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Distribution
    |--------------------------------------------------------------------------
    */
    public function executeDistribution(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $customers = $this->getCustomersForDistribution($data);
            $collectors = $this->getSelectedCollectors($data['collector_ids']);

            $distributionResult = $this->applyDistributionAlgorithm($customers, $collectors, $data['distribution_method']);

            $batchId = uniqid('dist_' . date('Ymd_'), true);

            $assignedCustomers = 0;
            $assignedCollectors = 0;
            $allCustomerIds = [];
            $collectorIds = []; // ✅ جديد

            foreach ($distributionResult as $collectorId => $collectorData) {
                if (!empty($collectorData['customers'])) {
                    $customerIds = collect($collectorData['customers'])->pluck('id')->toArray();

                    Customer::whereIn('id', $customerIds)
                        ->update(['relationship_manager_id' => $collectorId]);

                    $assignedCustomers += count($customerIds);
                    $assignedCollectors++;

                    $allCustomerIds = array_merge($allCustomerIds, $customerIds);

                    $collectorIds[] = $collectorId;

                    CustomerDistribution::create([
                        'batch_id' => $batchId,
                        'collector_id' => $collectorId,
                        'customers_count' => count($customerIds),
                        'total_debt' => $collectorData['total_debt'],
                        'distribution_method' => $data['distribution_method'],
                        'distribution_criteria' => json_encode($data),
                        'customer_ids' => json_encode($customerIds),
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $collectorIds = array_values(array_unique($collectorIds));

            return [
                'distributed_customers' => $assignedCustomers,
                'assigned_collectors' => $assignedCollectors,
                'batch_id' => $batchId,
                'customer_ids' => $allCustomerIds,
                'collector_ids' => $collectorIds,
                'distribution_result' => $distributionResult,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Distribution Algorithm
    |--------------------------------------------------------------------------
    */
    protected function applyDistributionAlgorithm(Collection $customers, Collection $collectors, string $method): array
    {
        switch ($method) {
            case self::METHOD_BALANCED_COUNT:
                return $this->balancedCountDistribution($customers, $collectors);
            case self::METHOD_HYBRID_BALANCED:
                return $this->hybridBalancedDistribution($customers, $collectors);
            case self::METHOD_BALANCED_AMOUNT:
            default:
                return $this->balancedAmountDistribution($customers, $collectors);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Balanced Amount Distribution Algorithm
    |--------------------------------------------------------------------------
    */
    protected function balancedAmountDistribution(Collection $customers, Collection $collectors): array
    {
        $result = [];
        $collectorsCount = $collectors->count();
        if ($collectorsCount === 0) return [];

        foreach ($collectors as $collector) {
            $result[$collector->id] = ['collector' => $collector, 'customers' => [], 'customers_count' => 0, 'total_debt' => 0];
        }

        foreach ($customers->sortByDesc('debt_amount') as $customer) {
            $targetCollectorId = collect($result)->sortBy('total_debt')->keys()->first();
            $result[$targetCollectorId]['customers'][] = $customer->toArray();
            $result[$targetCollectorId]['customers_count']++;
            $result[$targetCollectorId]['total_debt'] += $customer->debt_amount;
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Balanced Count Distribution Algorithm
    |--------------------------------------------------------------------------
    */
    protected function balancedCountDistribution(Collection $customers, Collection $collectors): array
    {
        $result = [];
        $collectorsCount = $collectors->count();
        if ($collectorsCount === 0) return [];

        foreach ($collectors as $collector) {
            $result[$collector->id] = ['collector' => $collector, 'customers' => [], 'customers_count' => 0, 'total_debt' => 0];
        }

        $customerIndex = 0;
        foreach ($customers as $customer) {
            $collectorId = $collectors[$customerIndex % $collectorsCount]->id;
            $result[$collectorId]['customers'][] = $customer->toArray();
            $result[$collectorId]['customers_count']++;
            $result[$collectorId]['total_debt'] += $customer->debt_amount;
            $customerIndex++;
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Hybrid Balanced Distribution Algorithm
    |--------------------------------------------------------------------------
    */
    protected function hybridBalancedDistribution(Collection $customers, Collection $collectors): array
    {
        $result = [];
        $collectorsCount = $collectors->count();
        if ($collectorsCount === 0) return [];

        $totalCustomers = $customers->count();
        $totalDebt = $customers->sum('debt_amount');

        foreach ($collectors as $collector) {
            $result[$collector->id] = [
                'collector' => $collector,
                'customers' => [],
                'customers_count' => 0,
                'total_debt' => 0,
            ];
        }

        $sortedCustomers = $customers->sortByDesc('debt_amount')->values();

        $collectorIds = $collectors->pluck('id')->toArray();
        $collectorIndex = 0;

        foreach ($sortedCustomers as $customer) {
            $targetCollectorId = $collectorIds[$collectorIndex % $collectorsCount];

            $result[$targetCollectorId]['customers'][] = $customer->toArray();
            $result[$targetCollectorId]['customers_count']++;
            $result[$targetCollectorId]['total_debt'] += $customer->debt_amount;

            $collectorIndex++;
        }

        $result = $this->optimizeDistribution($result, $totalDebt);

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Optimize Distribution for Better Balance
    |--------------------------------------------------------------------------
    */
    protected function optimizeDistribution(array $distribution, float $totalDebt): array
    {
        $collectorsCount = count($distribution);
        $targetDebtPerCollector = $totalDebt / $collectorsCount;
        $tolerance = $targetDebtPerCollector * 0.15;

        for ($iteration = 0; $iteration < 3; $iteration++) {
            $needsOptimization = false;

            foreach ($distribution as $fromCollectorId => $fromData) {
                if ($fromData['total_debt'] > $targetDebtPerCollector + $tolerance) {

                    foreach ($distribution as $toCollectorId => $toData) {
                        if (
                            $toData['total_debt'] < $targetDebtPerCollector - $tolerance &&
                            $fromCollectorId !== $toCollectorId
                        ) {

                            foreach ($fromData['customers'] as $index => $customer) {
                                $customerDebt = $customer['debt_amount'];

                                $newFromDebt = $fromData['total_debt'] - $customerDebt;
                                $newToDebt = $toData['total_debt'] + $customerDebt;

                                $fromDeviation = abs($newFromDebt - $targetDebtPerCollector);
                                $toDeviation = abs($newToDebt - $targetDebtPerCollector);
                                $currentFromDeviation = abs($fromData['total_debt'] - $targetDebtPerCollector);
                                $currentToDeviation = abs($toData['total_debt'] - $targetDebtPerCollector);

                                if ($fromDeviation + $toDeviation < $currentFromDeviation + $currentToDeviation) {
                                    $distribution[$toCollectorId]['customers'][] = $customer;
                                    $distribution[$toCollectorId]['customers_count']++;
                                    $distribution[$toCollectorId]['total_debt'] += $customerDebt;

                                    unset($distribution[$fromCollectorId]['customers'][$index]);
                                    $distribution[$fromCollectorId]['customers'] = array_values($distribution[$fromCollectorId]['customers']);
                                    $distribution[$fromCollectorId]['customers_count']--;
                                    $distribution[$fromCollectorId]['total_debt'] -= $customerDebt;

                                    $needsOptimization = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            if (!$needsOptimization) {
                break;
            }
        }

        return $distribution;
    }


    /*
    |--------------------------------------------------------------------------
    | Find Best Collector For Hybrid Distribution (Legacy - Not Used)
    |--------------------------------------------------------------------------
    */
    protected function findBestCollectorForHybrid(array $currentDistribution, float $customerDebt): int
    {
        return array_key_first($currentDistribution);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customers For Distribution Helper
    |--------------------------------------------------------------------------
    */
    protected function getCustomersForDistribution(array $data): Collection
    {
        if (!empty($data['customer_ids'])) {
            return Customer::whereIn('id', $data['customer_ids'])
                ->with('transactions')
                ->get()
                ->map(function ($customer) {
                    $customer->debt_amount = $customer->remaining_debt;
                    return $customer;
                });
        }
        return $this->getFilteredCustomersForDistribution($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Selected Collectors Helper
    |--------------------------------------------------------------------------
    */
    protected function getSelectedCollectors(array $collectorIds): Collection
    {
        return User::whereIn('id', $collectorIds)->role('collector')->select('id', 'name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Distribution Statistics
    |--------------------------------------------------------------------------
    */
    protected function calculateDistributionStatistics(array $distribution): array
    {
        $debts = collect($distribution)->pluck('total_debt');
        $counts = collect($distribution)->pluck('customers_count');

        return [
            'debt_std_deviation' => $this->calculateStandardDeviation($debts->toArray()),
            'debt_min' => $debts->min(),
            'debt_max' => $debts->max(),
            'debt_avg' => $debts->avg(),
            'balance_score' => $this->calculateBalanceScore($distribution),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Standard Deviation Helper
    |--------------------------------------------------------------------------
    */
    protected function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count === 0) return 0.0;
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / $count;
        return sqrt($variance);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Distribution Batch Details
    |--------------------------------------------------------------------------
    */
    public function getDistributionBatchDetails(string $batchId): array
    {
        $distributions = CustomerDistribution::where('batch_id', $batchId)
            ->with(['collector:id,name', 'createdBy:id,name'])
            ->get();

        if ($distributions->isEmpty()) {
            return [];
        }

        $firstDistribution = $distributions->first();

        return [
            'batch_id' => $batchId,
            'distribution_method' => $firstDistribution->distribution_method,
            'distribution_method_label' => $firstDistribution->distribution_method_label,
            'created_by' => $firstDistribution->createdBy->name ?? 'غير محدد',
            'created_at' => $firstDistribution->created_at,
            'total_collectors' => $distributions->count(),
            'total_customers' => $distributions->sum('customers_count'),
            'total_debt' => $distributions->sum('total_debt'),
            'collectors_details' => $distributions->map(function ($dist) {
                return [
                    'collector_name' => $dist->collector->name ?? 'غير محدد',
                    'customers_count' => $dist->customers_count,
                    'total_debt' => $dist->total_debt,
                    'customer_ids' => $dist->customer_ids,
                ];
            })->toArray()
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Balance Score Helper
    |--------------------------------------------------------------------------
    */
    protected function calculateBalanceScore(array $distribution): float
    {
        $debts = collect($distribution)->pluck('total_debt');
        if ($debts->count() <= 1) return 100.0;

        $mean = $debts->avg();
        if ($mean == 0) return 100.0;

        $maxDeviation = $debts->map(fn($debt) => abs($debt - $mean))->max();
        $balanceScore = max(0, 100 - (($maxDeviation / $mean) * 100));
        return round($balanceScore, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Companies and Portfolios with Unassigned Customers
    |--------------------------------------------------------------------------
    */
    public function getCompaniesAndPortfoliosWithUnassignedCustomers(): array
    {
        $companies = Company::whereHas('customers', function ($query) {
            $query->whereNull('relationship_manager_id');
        })
            ->active()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $allPortfolios = Portfolio::active()
            ->select('id', 'name', 'company_id')
            ->with('company:id,name')
            ->orderBy('name')
            ->get();

        return [
            'companies' => $companies,
            'portfolios' => $allPortfolios,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Target Calculator Methods
    |--------------------------------------------------------------------------
    | Calculate monthly targets based on distribution result and duration .
    */
    public function calculateMonthlyTargets(array $distributionResult, int $duration, int $startMonth, int $startYear): array
    {

        $monthlyTargets = [];

        foreach ($distributionResult as $collectorId => $data) {
            try {
                $totalAmount = floatval($data['total_debt']);
                $monthlyAmount = floor($totalAmount / $duration);
                $remainder = $totalAmount - ($monthlyAmount * $duration);

                // الحل: استخدام isset للتحقق من النوع
                $collectorName = isset($data['collector']['name'])
                    ? $data['collector']['name']  // array
                    : (isset($data['collector']->name) ? $data['collector']->name : 'غير محدد');  // object

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
    /*
    |--------------------------------------------------------------------------
    | Target Calculator Methods .
    |--------------------------------------------------------------------------
    | Create targets from calculator settings .
    */
    public function createTargetsFromCalculator(array $collectorIds, array $calculatorSettings, string $batchId): array
    {
        $monthlyTarget = floatval($calculatorSettings['monthly_target']);
        $duration = intval($calculatorSettings['duration']);
        $created = 0;

        if (empty($collectorIds)) {
            throw new \Exception('لا توجد معرفات محصلين لإنشاء الأهداف');
        }

        if ($monthlyTarget <= 0 || $duration <= 0) {
            throw new \Exception('بيانات الحاسبة غير صالحة');
        }

        try {
            DB::transaction(function () use ($collectorIds, $monthlyTarget, $duration, $batchId, &$created) {
                foreach ($collectorIds as $collectorId) {

                    $collector = User::find($collectorId);

                    if (!$collector) {
                        continue;
                    }

                    $startDate = $this->findNextAvailableMonth($collectorId);

                    for ($i = 0; $i < $duration; $i++) {
                        $targetDate = $startDate->copy()->addMonths($i);

                        $targetData = [
                            'user_id' => $collectorId,
                            'target_type' => 'monthly',
                            'target_month' => $targetDate->month,
                            'target_year' => $targetDate->year,
                            'target_amount' => $monthlyTarget,
                            'status' => 'active',
                            'added_by' => Auth::id(),
                        ];


                        CollectionTarget::create($targetData);
                        $created++;
                    }
                }
            });


            return [
                'created' => $created,
                'message' => "تم إنشاء {$created} هدف بنجاح"
            ];
        } catch (\Exception $e) {
            throw new \Exception('فشل في إنشاء الأهداف: ' . $e->getMessage());
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Find Next Available Month For Collector (NEW - PRIVATE)
    |--------------------------------------------------------------------------
    | البحث عن أول شهر خالي (ليس لديه هدف) للمحصل
    */
    private function findNextAvailableMonth(int $collectorId): Carbon
    {
        $currentDate = Carbon::now()->startOfMonth();
        $maxIterations = 24;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $exists = CollectionTarget::where('user_id', $collectorId)
                ->where('target_month', $currentDate->month)
                ->where('target_year', $currentDate->year)
                ->where('target_type', 'monthly')
                ->exists();

            if (!$exists) {
                return $currentDate;
            }

            $currentDate->addMonth();
            $iteration++;
        }

        return Carbon::now()->startOfMonth();
    }


    /*
    |--------------------------------------------------------------------------
    | Target Calculator Methods
    |--------------------------------------------------------------------------
    | Get month name in Arabic .
    */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر'
        ];

        return $months[$month] ?? '';
    }
}
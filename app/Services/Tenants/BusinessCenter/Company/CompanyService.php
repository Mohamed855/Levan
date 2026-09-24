<?php

namespace App\Services\Tenants\BusinessCenter\Company;

use App\Data\Tenants\BusinessCenter\Company\CompanyCreateDTO;
use App\Data\Tenants\BusinessCenter\Company\CompanyUpdateDTO;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Company\CompanyAuthorizedPerson;
use App\Models\Tenants\BusinessCenter\Company\CompanyTarget;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /*
    |--------------------------------------------------------------------------
    | Create Company
    |--------------------------------------------------------------------------
    */
    public function createCompany(CompanyCreateDTO $dto): Company
    {
        return DB::transaction(function () use ($dto) {
            $company = Company::create($dto->toModelArray());

            if (!empty($dto->authorized_people)) {
                $this->syncAuthorizedPeople($company, $dto->authorized_people);
            }

            if ($dto->target_percentage !== null && \Illuminate\Support\Facades\Schema::hasTable('company_targets')) {
                CompanyTarget::create([
                    'company_id' => $company->id,
                    'target_percentage' => $dto->target_percentage,
                    'effective_from' => now()->startOfDay(),
                    'effective_to' => null,
                    'changed_by' => auth()->id(),
                ]);
            }

            $relations = ['authorizedPeople'];
            if (\Illuminate\Support\Facades\Schema::hasTable('company_targets')) {
                $relations[] = 'currentTarget';
            }

            return $company->fresh($relations);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Company
    |--------------------------------------------------------------------------
    */
    public function updateCompany(Company $company, CompanyUpdateDTO $dto): Company
    {
        return DB::transaction(function () use ($company, $dto) {
            $company->update($dto->toModelArray());

            if ($dto->wasProvided('authorized_people')) {
                CompanyAuthorizedPerson::where('company_id', $company->id)->delete();

                if (!empty($dto->authorized_people)) {
                    $this->syncAuthorizedPeople($company, $dto->authorized_people);
                }
            }

            if ($dto->target_percentage !== null && \Illuminate\Support\Facades\Schema::hasTable('company_targets')) {
                $currentTarget = $company->currentTarget;

                if (!$currentTarget || $currentTarget->target_percentage != $dto->target_percentage) {

                    if ($currentTarget) {
                        $currentTarget->update([
                            'effective_to' => now()->subSecond(),
                        ]);
                    }

                    CompanyTarget::create([
                        'company_id' => $company->id,
                        'target_percentage' => $dto->target_percentage,
                        'effective_from' => now(),
                        'effective_to' => null,
                        'changed_by' => auth()->id(),
                    ]);
                }
            }

            $relations = ['authorizedPeople'];
            if (\Illuminate\Support\Facades\Schema::hasTable('company_targets')) {
                $relations[] = 'currentTarget';
            }

            return $company->fresh($relations);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Authorized People
    |--------------------------------------------------------------------------
    | إدراج المفوضين مع ضمان وجود مفوض افتراضي واحد فقط
    */
    private function syncAuthorizedPeople(Company $company, array $people): void
    {
        $hasDefault = collect($people)->contains(fn($p) => $p->is_default === true);
        if (!$hasDefault && count($people) === 1) {
            $people[0]->is_default = true;
            $hasDefault = true;
        }
        $defaultCount = 0;
        foreach ($people as $person) {
            if ($person->is_default) {
                $defaultCount++;
                if ($defaultCount > 1) {
                    $person->is_default = false;
                }
            }
        }

        $payload = array_map(function ($p) use ($company) {
            $data = $p->toModelArray();
            $data['company_id'] = $company->id;
            $data['created_at'] = now();
            $data['updated_at'] = now();
            return $data;
        }, $people);

        CompanyAuthorizedPerson::insert($payload);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Company
    |--------------------------------------------------------------------------
    */
    public function deleteCompany(Company $company): bool
    {
        return DB::transaction(function () use ($company) {
            if ($company->portfolios()->count() > 0) {
                throw new \Exception('لا يمكن حذف الشركة لأنها تحتوي على محافظ. يجب حذف المحافظ أولاً.');
            }

            if ($company->customers()->count() > 0) {
                throw new \Exception('لا يمكن حذف الشركة لأنها تحتوي على عملاء. يجب حذف العملاء أولاً.');
            }

            return $company->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Base Companies Query
    |--------------------------------------------------------------------------
    */
    public function getCompaniesQuery(): Builder
    {
        return Company::query()
            ->select(['id', 'name', 'status', 'logo_path', 'created_at'])
            ->withCount('authorizedPeople');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Company Details For Show Page
    |--------------------------------------------------------------------------
    */
    public function getCompanyDetails(Company $company): array
    {
        $company->loadCount('authorizedPeople');

        $portfolios = $company->portfolios()
            ->withCount('customers')
            ->get();

        $contract = \Illuminate\Support\Facades\Schema::hasTable('contracts')
            ? Contract::forCompany($company->id)->latest('start_date')->first()
            : null;

        return [
            'authorizedCount' => (int) $company->authorized_people_count,
            'portfolios'      => $portfolios,
            'portfoliosCount' => $portfolios->count(),
            'customersCount'  => (int) $portfolios->sum('customers_count'),
            'contract'        => $contract,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Company Statistics
    |--------------------------------------------------------------------------
    */
    public function getCompanyStatistics(): array
    {
        $baseQuery = $this->getCompaniesQuery();
        $totalCredit = CustomerTransaction::where('type', 'credit')->sum('amount');
        $totalDebit  = CustomerTransaction::where('type', 'debit')->sum('amount');
        $totalCollectionDebt = $totalDebit > 0
            ? round(($totalCredit / $totalDebit) * 100, 2)
            : 0;
        return [
            'total'    => (clone $baseQuery)->count(),
            'active'   => (clone $baseQuery)->where('status', 'active')->count(),
            'companyHasPortfolios' => (clone $baseQuery)->where('status', 'active')->whereHas('portfolios', function ($q) {
                $q->where('status', 'active');
            })->count(),
            'companyNotHasPortfolios' => (clone $baseQuery)
                ->where('status', 'active')
                ->whereDoesntHave('portfolios', function ($q) {
                    $q->where('status', '=', 'active');
                })
                ->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'archived' => Company::onlyTrashed()->count(),
            'recent'   => Company::where('created_at', '>=', now()->subDays(30))->count(),
            'totalCusomtersForAllCompanies' => Customer::count(),
            'totalDeptForAllCompanies' => CustomerTransaction::where('type', 'debit')->sum('amount'),
            'totalCreditForAllCompanies' => CustomerTransaction::where('type', 'credit')->sum('amount'),
            'safi' => CustomerTransaction::where('type', 'debit')->sum('amount') - CustomerTransaction::where('type', 'credit')->sum('amount'),
            'totalCollectionDebt' => $totalCollectionDebt,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Companies
    |--------------------------------------------------------------------------
    */
    public function filterCompaniesQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('authorizedPeople', function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('position', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Data - Top 5 Companies by Total Debt
    |--------------------------------------------------------------------------
    */
    public function getTopCompaniesByDebt($limit = 5): array
    {
        $companies = Company::select('id', 'name')
            ->withCount('customers')
            ->get()
            ->map(function ($company) {
                $debt = $company->customers()
                    ->join('customer_transactions', 'customers.id', '=', 'customer_transactions.customer_id')
                    ->where('customer_transactions.type', 'debit')
                    ->sum('customer_transactions.amount');
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'debt' => (float) $debt,
                ];
            })
            ->filter(function ($company) {
                return $company['debt'] > 0;
            })
            ->sortByDesc('debt')
            ->take($limit)
            ->values()
            ->toArray();

        return [
            'labels' => array_column($companies, 'name'),
            'data' => array_column($companies, 'debt'),
            'companies' => $companies,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Data - Customer Distribution by Company
    |--------------------------------------------------------------------------
    */
    public function getCustomerDistributionByCompany(): array
    {
        $companies = Company::select('id', 'name')
            ->withCount('customers')
            ->get()
            ->map(function ($company) {
                return [
                    'name' => $company->name,
                    'count' => $company->customers_count,
                ];
            })
            ->filter(fn($c) => $c['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->toArray();

        return [
            'labels' => array_column($companies, 'name'),
            'data' => array_column($companies, 'count'),
            'companies' => $companies,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Data - Collection Rate Trend (Last 6 Months for Top 5 Companies)
    |--------------------------------------------------------------------------
    */
    public function getCollectionRateTrend($months = 6, $limit = 5): array
    {
        // Get top 5 companies by debt
        $topCompanies = Company::select('id', 'name')
            ->get()
            ->map(function ($company) {
                $debt = $company->customers()
                    ->join('customer_transactions', 'customers.id', '=', 'customer_transactions.customer_id')
                    ->where('customer_transactions.type', 'debit')
                    ->sum('customer_transactions.amount');
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'debt' => (float) $debt,
                ];
            })
            ->sortByDesc('debt')
            ->take($limit)
            ->toArray();

        // Generate dates for last N months
        $dates = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $dates[] = now()->subMonths($i)->format('Y-m');
        }

        $chartData = [];
        foreach ($topCompanies as $company) {
            $datasets = [];
            foreach ($dates as $date) {
                $startDate = $date . '-01';
                $endDate = now()->createFromFormat('Y-m', $date)->endOfMonth()->format('Y-m-d');

                $debt = $company['debt']; // Total debt
                $credit = $company['id'];

                $credit = DB::table('customer_transactions')
                    ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
                    ->where('customers.company_id', $company['id'])
                    ->where('customer_transactions.type', 'credit')
                    ->whereBetween('customer_transactions.transaction_date', [$startDate, $endDate])
                    ->sum('customer_transactions.amount');

                $totalDebt = DB::table('customer_transactions')
                    ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
                    ->where('customers.company_id', $company['id'])
                    ->where('customer_transactions.type', 'debit')
                    ->whereBetween('customer_transactions.transaction_date', [$startDate, $endDate])
                    ->sum('customer_transactions.amount');

                $percentage = $totalDebt > 0 ? round(($credit / $totalDebt) * 100, 2) : 0;
                $datasets[] = $percentage;
            }

            $chartData[] = [
                'label' => $company['name'],
                'data' => $datasets,
            ];
        }

        return [
            'labels' => $dates,
            'datasets' => $chartData,
        ];
    }
}

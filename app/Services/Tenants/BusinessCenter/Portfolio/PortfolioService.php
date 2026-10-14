<?php

namespace App\Services\Tenants\BusinessCenter\Portfolio;

use App\Data\Tenants\BusinessCenter\Portfolio\PortfolioCreateDTO;
use App\Data\Tenants\BusinessCenter\Portfolio\PortfolioFieldInput;
use App\Data\Tenants\BusinessCenter\Portfolio\PortfolioUpdateDTO;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    /*
    |--------------------------------------------------------------------------
    | Portfolio Creation
    |--------------------------------------------------------------------------
    */
    public function createPortfolio(PortfolioCreateDTO $dto): Portfolio
    {
        return DB::transaction(function () use ($dto) {
            $portfolio = Portfolio::create([
                'name' => $dto->name,
                'company_id' => $dto->company_id,
                'assignment_date' => $dto->assignment_date,
                'call_start_time' => $dto->call_start_time,
                'call_end_time' => $dto->call_end_time,
                'has_split_names' => $dto->has_split_names,
                'status' => $dto->status ?? 'active',
                'user_id' => $dto->user_id,
            ]);

            if (! empty($dto->fields)) {
                $this->storeFieldsCollection($portfolio, $dto->fields);
            }

            return $portfolio->fresh(['company:id,name', 'portfolioFields']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Portfolio Update
    |--------------------------------------------------------------------------
    */
    public function updatePortfolio(Portfolio $portfolio, PortfolioUpdateDTO $dto): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $dto) {
            $portfolio->update([
                'name' => $dto->name,
                'company_id' => $dto->company_id,
                'assignment_date' => $dto->assignment_date,
                'call_start_time' => $dto->call_start_time,
                'call_end_time' => $dto->call_end_time,
                'has_split_names' => $dto->has_split_names,
                'status' => $dto->status ?? $portfolio->status,
                'user_id' => $dto->user_id,
            ]);

            if (! empty($dto->fields)) {
                $this->syncFieldsCollection($portfolio, $dto->fields);
            } else {
                $this->clearPortfolioFields($portfolio);
            }

            return $portfolio->fresh(['company:id,name', 'portfolioFields']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Portfolio Deletion
    |--------------------------------------------------------------------------
    */
    public function deletePortfolio(Portfolio $portfolio): bool
    {
        return DB::transaction(function () use ($portfolio) {
            if (method_exists($portfolio, 'customers') && $portfolio->customers()->count() > 0) {
                throw new \Exception('لا يمكن حذف المحفظة لأنها تحتوي على عملاء. يجب حذف العملاء أولاً.');
            }
            $this->clearPortfolioFields($portfolio);

            return $portfolio->delete();
        });
    }

    public function updateStatus(Portfolio $portfolio, string $status): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $status): Portfolio {
            $portfolio->update(['status' => $status]);

            return $portfolio->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Store Fields Collection
    |--------------------------------------------------------------------------
    */
    protected function storeFieldsCollection(Portfolio $portfolio, array $inputs): void
    {
        foreach ($inputs as $input) {
            if (! $input instanceof PortfolioFieldInput) {
                continue;
            }

            $portfolio->portfolioFields()->create([
                'field_name' => $input->name,
                'field_type' => $input->type,
                'is_required' => $input->is_required,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Fields Collection
    |--------------------------------------------------------------------------
    */
    protected function syncFieldsCollection(Portfolio $portfolio, array $inputs): void
    {
        $existing = $portfolio->portfolioFields()->get()->keyBy('id');

        foreach ($inputs as $input) {
            if (! $input instanceof PortfolioFieldInput) {
                continue;
            }

            if ($input->id && $existing->has($input->id)) {
                $existing[$input->id]->update([
                    'field_name' => $input->name,
                    'field_type' => $input->type,
                    'is_required' => $input->is_required,
                ]);
                $existing->forget($input->id);
            } else {
                $portfolio->portfolioFields()->create([
                    'field_name' => $input->name,
                    'field_type' => $input->type,
                    'is_required' => $input->is_required,
                ]);
            }
        }

        foreach ($existing as $toRemove) {
            $toRemove->delete();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Fields
    |--------------------------------------------------------------------------
    */
    protected function clearPortfolioFields(Portfolio $portfolio): void
    {
        $portfolio->portfolioFields()->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */
    public function getPortfoliosQuery(): Builder
    {
        return Portfolio::select(['id', 'name', 'company_id', 'assignment_date', 'status', 'created_at'])
            ->with(['company:id,name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */
    public function getPortfolioStatistics(): array
    {
        $base = $this->getPortfoliosQuery();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'archived' => Portfolio::onlyTrashed()->count(),
            'recent_assignments' => (clone $base)
                ->where('assignment_date', '>=', now()->subDays(30))
                ->whereNotNull('assignment_date')
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    */
    public function filterPortfoliosQuery(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Active Companies
    |--------------------------------------------------------------------------
    */
    public function getActiveCompanies()
    {
        return Company::active()->select('id', 'name')->orderBy('name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Assignments
    |--------------------------------------------------------------------------
    */
    public function getRecentAssignments(int $days = 30): Builder
    {
        return $this->getPortfoliosQuery()
            ->where('assignment_date', '>=', now()->subDays($days))
            ->whereNotNull('assignment_date')
            ->orderBy('assignment_date', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Build Portfolio Show View Data
    |--------------------------------------------------------------------------
    */
    public function buildShowViewData(Portfolio $portfolio): array
    {
        $portfolio->load([
            'company:id,name',
            'portfolioFields:id,portfolio_id,field_name,field_type,is_required',
        ])->loadCount('customers');

        $financialData = $this->calculatePortfolioFinancials($portfolio);

        $statusText = $portfolio->status === 'active' ? 'نشط' : 'غير نشط';
        $statusClass = $portfolio->status === 'active' ? 'bg-label-success' : 'bg-label-danger';

        return [
            'pageTitle' => 'عرض المحفظة',
            'portfolioId' => $portfolio->id,
            'portfolioName' => $portfolio->name,
            'companyName' => $portfolio->company->name ?? 'غير محدد',
            'statusText' => $statusText,
            'statusClass' => $statusClass,
            'customersCount' => (int) $portfolio->customers_count,
            'fieldsCount' => $portfolio->portfolioFields->count(),
            'assignmentDateLong' => $portfolio->formatted_assignment_date ?? 'غير محدد',
            'assignmentDateShort' => $portfolio->assignment_date?->format('Y-m-d') ?? 'غير محدد',

            'callStartTime' => $portfolio->call_start_time?->format('H:i') ?? null,
            'callEndTime' => $portfolio->call_end_time?->format('H:i') ?? null,
            'callStartTimeFormatted' => $portfolio->formatted_call_start_time ?? 'غير محدد',
            'callEndTimeFormatted' => $portfolio->formatted_call_end_time ?? 'غير محدد',
            'callTimeRange' => $portfolio->call_time_range ?? 'غير محدد',

            'hasSplitNamesText' => $portfolio->has_split_names ? 'مفعل' : 'غير مفعل',
            'createdAt' => $portfolio->created_at?->format('Y-m-d H:i'),
            'updatedAt' => $portfolio->updated_at?->format('Y-m-d H:i'),
            'totalDebt' => $financialData['total_debt'],
            'totalDebtFormatted' => number_format($financialData['total_debt'], 2),
            'totalCollected' => $financialData['total_collected'],
            'totalCollectedFormatted' => number_format($financialData['total_collected'], 2),
            'remainingDebt' => $financialData['remaining_debt'],
            'remainingDebtFormatted' => number_format($financialData['remaining_debt'], 2),
            'collectionRate' => $financialData['collection_rate'],
            'collectionRateFormatted' => number_format($financialData['collection_rate'], 1),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Portfolio Financials
    |--------------------------------------------------------------------------
    */
    private function calculatePortfolioFinancials(Portfolio $portfolio): array
    {
        $customers = $portfolio->customers()
            ->with(['transactions' => function ($query) {
                $query->select('customer_id', 'type', 'amount');
            }])
            ->get(['id']);

        $totalDebt = 0;
        $totalCollected = 0;

        foreach ($customers as $customer) {
            $customerDebt = $customer->transactions
                ->where('type', 'debit')
                ->sum('amount');
            $customerCollected = $customer->transactions
                ->where('type', 'credit')
                ->sum('amount');

            $totalDebt += $customerDebt;
            $totalCollected += $customerCollected;
        }

        $remainingDebt = $totalDebt - $totalCollected;
        $collectionRate = $totalDebt > 0 ? ($totalCollected / $totalDebt) * 100 : 0;

        return [
            'total_debt' => $totalDebt,
            'total_collected' => $totalCollected,
            'remaining_debt' => $remainingDebt,
            'collection_rate' => $collectionRate,
        ];
    }
}

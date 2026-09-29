<?php

namespace App\Services\Tenants\Archive\Portfolio;

use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PortfolioArchiveService
{
    /*
    |--------------------------------------------------------------------------
    | Restore Portfolio
    |--------------------------------------------------------------------------
    | Handle portfolio restoration with all related operations.
    */
    public function restorePortfolio(Portfolio $portfolio): Portfolio
    {
        return DB::transaction(function () use ($portfolio) {
            $portfolio->restore();
            return $portfolio->fresh(['company']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete Portfolio
    |--------------------------------------------------------------------------
    | Handle portfolio permanent deletion with cleanup.
    */
    public function forceDeletePortfolio(Portfolio $portfolio): bool
    {
        return DB::transaction(function () use ($portfolio) {
            return $portfolio->forceDelete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Base Archived Portfolios Query
    |--------------------------------------------------------------------------
    | Get base query for archived portfolios with selected columns.
    */
    public function getArchivedPortfoliosQuery(): Builder
    {
        return Portfolio::onlyTrashed()
            ->select(['id', 'name', 'company_id', 'assignment_date', 'status', 'deleted_at', 'created_at'])
            ->with(['company:id,name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Archived Portfolio Statistics
    |--------------------------------------------------------------------------
    | Get archived portfolio statistics for dashboard.
    */
    public function getArchivedPortfolioStatistics(): array
    {
        $baseQuery = $this->getArchivedPortfoliosQuery();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'recently_archived' => (clone $baseQuery)
                ->where('deleted_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Archived Portfolios Query
    |--------------------------------------------------------------------------
    | Apply filters to archived portfolios query.
    */
    public function filterArchivedPortfoliosQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['search_query'])) {
            $searchTerm = $filters['search_query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Restore Portfolios
    |--------------------------------------------------------------------------
    | Restore multiple portfolios at once.
    */
    public function massRestorePortfolios(array $portfolioIds): int
    {
        return DB::transaction(function () use ($portfolioIds) {
            $portfolios = Portfolio::onlyTrashed()->whereIn('id', $portfolioIds)->get();

            foreach ($portfolios as $portfolio) {
                $portfolio->restore();
            }

            return $portfolios->count();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete Portfolios
    |--------------------------------------------------------------------------
    | Permanently delete multiple portfolios at once.
    */
    public function massForceDeletePortfolios(array $portfolioIds): int
    {
        return DB::transaction(function () use ($portfolioIds) {
            $portfolios = Portfolio::onlyTrashed()->whereIn('id', $portfolioIds)->get();

            foreach ($portfolios as $portfolio) {
                $portfolio->forceDelete();
            }

            return $portfolios->count();
        });
    }
}

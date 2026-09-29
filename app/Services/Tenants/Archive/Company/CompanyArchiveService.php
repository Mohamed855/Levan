<?php

namespace App\Services\Tenants\Archive\Company;

use App\Models\Tenants\BusinessCenter\Company\Company;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyArchiveService
{
    /*
    |--------------------------------------------------------------------------
    | Restore Company
    |--------------------------------------------------------------------------
    | Handle company restoration with all related operations.
    */
    public function restoreCompany(Company $company): Company
    {
        return DB::transaction(function () use ($company) {
            $company->restore();
            return $company->fresh(['authorizedPeople']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete Company
    |--------------------------------------------------------------------------
    | Handle company permanent deletion with cleanup.
    */
    public function forceDeleteCompany(Company $company): bool
    {
        return DB::transaction(function () use ($company) {
            if ($company->logo_path && Storage::disk('tenant_public')->exists($company->logo_path)) {
                Storage::disk('tenant_public')->delete($company->logo_path);
            }

            return $company->forceDelete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Base Archived Companies Query
    |--------------------------------------------------------------------------
    | Get base query for archived companies with selected columns.
    */
    public function getArchivedCompaniesQuery(): Builder
    {
        return Company::onlyTrashed()
            ->select(['id', 'name', 'status', 'logo_path', 'deleted_at', 'created_at'])
            ->withCount('authorizedPeople');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Archived Company Statistics
    |--------------------------------------------------------------------------
    | Get archived company statistics for dashboard.
    */
    public function getArchivedCompanyStatistics(): array
    {
        $baseQuery = $this->getArchivedCompaniesQuery();

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
    | Filter Archived Companies Query
    |--------------------------------------------------------------------------
    | Apply filters to archived companies query.
    */
    public function filterArchivedCompaniesQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search_query'])) {
            $searchTerm = $filters['search_query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('authorizedPeople', function ($qq) use ($searchTerm) {
                        $qq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('phone', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%")
                            ->orWhere('position', 'like', "%{$searchTerm}%");
                    });
            });
        }

        if (!empty($filters['authorized_count'])) {
            switch ($filters['authorized_count']) {
                case '0':
                    $query->has('authorizedPeople', '=', 0);
                    break;
                case '1':
                    $query->has('authorizedPeople', '=', 1);
                    break;
                case '2-5':
                    $query->has('authorizedPeople', '>=', 2)
                        ->has('authorizedPeople', '<=', 5);
                    break;
                case '6+':
                    $query->has('authorizedPeople', '>=', 6);
                    break;
            }
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Restore Companies
    |--------------------------------------------------------------------------
    | Restore multiple companies at once.
    */
    public function massRestoreCompanies(array $companyIds): int
    {
        return DB::transaction(function () use ($companyIds) {
            $companies = Company::onlyTrashed()->whereIn('id', $companyIds)->get();
            
            foreach ($companies as $company) {
                $company->restore();
            }

            return $companies->count();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete Companies
    |--------------------------------------------------------------------------
    | Permanently delete multiple companies at once.
    */
    public function massForceDeleteCompanies(array $companyIds): int
    {
        return DB::transaction(function () use ($companyIds) {
            $companies = Company::onlyTrashed()->whereIn('id', $companyIds)->get();
            
            foreach ($companies as $company) {
                if ($company->logo_path && Storage::disk('tenant_public')->exists($company->logo_path)) {
                    Storage::disk('tenant_public')->delete($company->logo_path);
                }
                
                $company->forceDelete();
            }

            return $companies->count();
        });
    }
}
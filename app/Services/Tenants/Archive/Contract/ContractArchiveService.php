<?php

namespace App\Services\Tenants\Archive\Contract;

use App\Models\Tenants\BusinessCenter\Contract\Contract;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ContractArchiveService
{
    /*
    |--------------------------------------------------------------------------
    | استرجاع العقد من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function restoreContract(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            $contract->restore();
            return $contract->fresh(['company', 'creator', 'updater']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | حذف العقد نهائياً من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function forceDeleteContract(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            $contract->commissions()->forceDelete();
            $contract->debtTypes()->detach();
            return $contract->forceDelete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | استعلام العقود المؤرشفة المحسن للأداء
    |--------------------------------------------------------------------------
    */
    public function getArchivedContractsQuery(): Builder
    {
        return Contract::onlyTrashed()
            ->select([
                'contracts.id',
                'contracts.contract_number',
                'contracts.company_id',
                'contracts.start_date',
                'contracts.end_date',
                'contracts.status',
                'contracts.commission_type',
                'contracts.auto_renewal',
                'contracts.deleted_at',
                'contracts.created_at'
            ])
            ->with([
                'company:id,name,logo_path',
                'creator:id,name',
                'updater:id,name'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | إحصائيات العقود المؤرشفة
    |--------------------------------------------------------------------------
    */
    public function getArchivedContractStatistics(): array
    {
        $baseQuery = Contract::onlyTrashed();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'expired' => (clone $baseQuery)->where('status', 'expired')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
            'recently_archived' => (clone $baseQuery)->where('deleted_at', '>=', now()->subDays(30))->count(),
            'by_commission_type' => (clone $baseQuery)
                ->select('commission_type', DB::raw('count(*) as count'))
                ->groupBy('commission_type')
                ->get()
                ->pluck('count', 'commission_type')
                ->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر على استعلام العقود المؤرشفة
    |--------------------------------------------------------------------------
    */
    public function filterArchivedContractsQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status']) && is_string($filters['status'])) {
            $query->where('contracts.status', $filters['status']);
        }

        if (!empty($filters['company_id']) && is_numeric($filters['company_id'])) {
            $query->where('contracts.company_id', $filters['company_id']);
        }

        if (!empty($filters['commission_type']) && is_string($filters['commission_type'])) {
            $query->where('contracts.commission_type', $filters['commission_type']);
        }

        if (!empty($filters['auto_renewal']) && in_array($filters['auto_renewal'], ['0', '1'])) {
            $query->where('contracts.auto_renewal', (bool) $filters['auto_renewal']);
        }

        if (!empty($filters['search'])) {
            $searchTerm = is_array($filters['search']) ? 
                trim(implode(' ', $filters['search'])) : 
                trim((string) $filters['search']);
            
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('contracts.contract_number', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                            $companyQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            }
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | استرجاع جماعي للعقود
    |--------------------------------------------------------------------------
    */
    public function massRestoreContracts(array $contractIds): int
    {
        return DB::transaction(function () use ($contractIds) {
            $contracts = Contract::onlyTrashed()->whereIn('id', $contractIds)->get();

            foreach ($contracts as $contract) {
                $contract->restore();
            }

            return $contracts->count();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | حذف نهائي جماعي للعقود
    |--------------------------------------------------------------------------
    */
    public function massForceDeleteContracts(array $contractIds): int
    {
        return DB::transaction(function () use ($contractIds) {
            $contracts = Contract::onlyTrashed()->whereIn('id', $contractIds)->get();

            foreach ($contracts as $contract) {
                $contract->commissions()->forceDelete();
                $contract->debtTypes()->detach();
                $contract->forceDelete();
            }

            return $contracts->count();
        });
    }
}
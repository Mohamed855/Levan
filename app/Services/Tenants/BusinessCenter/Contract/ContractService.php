<?php

namespace App\Services\Tenants\BusinessCenter\Contract;

use App\Data\Tenants\BusinessCenter\Contract\ContractCreateDTO;
use App\Data\Tenants\BusinessCenter\Contract\ContractUpdateDTO;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    /*
    |--------------------------------------------------------------------------
    | Create New Contract (DTO)
    |--------------------------------------------------------------------------
    | Handle contract creation with DTO and commission slices.
    */
    public function createContract(ContractCreateDTO $dto): Contract
    {
        return DB::transaction(function () use ($dto) {
            $contract = Contract::create($dto->toModelArray());
            if (!empty($dto->commissions)) {
                $rows = array_map(fn($c) => $c->toModelArray(), $dto->commissions);
                $contract->commissions()->createMany($rows);
            }
            return $contract;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Contract (DTO)
    |--------------------------------------------------------------------------
    | Update contract with DTO and resync commission slices.
    */
    public function updateContract(Contract $contract, ContractUpdateDTO $dto): Contract
    {
        return DB::transaction(function () use ($contract, $dto) {
            $contract->update($dto->toModelArray());

            if ($dto->wasProvided('commissions')) {
                $contract->commissions()->delete();
                if (!empty($dto->commissions)) {
                    $rows = array_map(fn($c) => $c->toModelArray(), $dto->commissions);
                    $contract->commissions()->createMany($rows);
                }
            }

            return $contract->fresh(['commissions', 'company']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Contract
    |--------------------------------------------------------------------------
    | Delete contract and cleanup.
    */
    public function deleteContract(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            $contract->commissions()->delete();
            return $contract->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Contract Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(Contract $contract): Contract
    {
        $newStatus = $contract->status === 'active' ? 'inactive' : 'active';
        $contract->update(['status' => $newStatus]);
        return $contract;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Companies
    |--------------------------------------------------------------------------
    */
    public function getAvailableCompanies()
    {
        return Company::where('status', 'active')->orderBy('name')->select('id', 'name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Contract Number
    |--------------------------------------------------------------------------
    */
    public function generateContractNumber(): string
    {
        $prefix = 'CON-' . date('Y');
        $last = Contract::where('contract_number', 'like', $prefix . '%')->latest('id')->first();
        $seq = $last ? ((int)Str::after($last->contract_number, $prefix . '-')) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Contract Number Availability
    |--------------------------------------------------------------------------
    */
    public function isContractNumberAvailable(string $contractNumber, ?int $contractId = null): bool
    {
        $q = Contract::where('contract_number', $contractNumber);
        if ($contractId) $q->where('id', '!=', $contractId);
        return !$q->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Contract Statistics
    |--------------------------------------------------------------------------
    */
    public function getContractStatistics(): array
    {
        $base = Contract::query();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'expired' => (clone $base)->where('status', 'expired')->count(),
            'expiring_soon' => (clone $base)->expiringSoon(30)->count(),
            'by_commission_type' => (clone $base)
                ->select('commission_type', DB::raw('count(*) as count'))
                ->groupBy('commission_type')
                ->get()
                ->pluck('count', 'commission_type')
                ->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Contracts Expiring Soon
    |--------------------------------------------------------------------------
    */
    public function getContractsExpiringSoon(int $days = 30)
    {
        return Contract::with('company:id,name')->expiringSoon($days);
    }
}

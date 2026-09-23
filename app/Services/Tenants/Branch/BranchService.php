<?php

namespace App\Services\Tenants\Branch;

use App\Models\Tenants\Branch\Branch;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء فرع جديد
    |--------------------------------------------------------------------------
    */
    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id();
            return Branch::create($data);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث الفرع
    |--------------------------------------------------------------------------
    */
    public function update(Branch $branch, array $data): bool
    {
        return DB::transaction(function () use ($branch, $data) {
            return $branch->update($data);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | إحصائيات الفروع
    |--------------------------------------------------------------------------
    */
    public function getStatistics(): array
    {
        return [
            'total'    => Branch::count(),
            'active'   => Branch::where('status', 'active')->count(),
            'inactive' => Branch::where('status', 'inactive')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | توليد كود فرع جديد
    |--------------------------------------------------------------------------
    */
    public function generateCode(): string
    {
        $lastBranch = Branch::orderBy('id', 'desc')->first();
        $number = $lastBranch ? $lastBranch->id + 1 : 1;
        return 'BR' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
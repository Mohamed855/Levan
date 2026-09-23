<?php

namespace App\Services\Admin\Plans;


use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\DB;

class PlanService
{
    /*
    |--------------------------------------------------------------------------
    | Create plan
    |--------------------------------------------------------------------------
    */
    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {

            $features = [];
            if (!empty($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (!empty($feature)) {
                        $features[] = $feature;
                    }
                }
            }

            $plan = Plan::create([
                ...$data,
                'features'      => $features,
                'created_by'    => auth()->id(),
            ]);


            return $plan;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update plan
    |--------------------------------------------------------------------------
    */
    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {

            // تجهيز الميزات
            $features = [];
            if (!empty($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (!empty($feature)) {
                        $features[] = $feature;
                    }
                }
            }


            // تحديث الباقة
            $plan->update([
                ...$data,
                'features'      => $features ?: $plan->features,
                'updated_by'    => auth()->id(),
            ]);


            return $plan;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete plan
    |--------------------------------------------------------------------------
    */
    public function delete(Plan $plan): bool
    {
        return DB::transaction(function () use ($plan) {
            return $plan->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get plan statistics
    |--------------------------------------------------------------------------
    */
    public function getPlanStatistics(): array
    {
        $baseQuery = Plan::query();

        return [
            'total'    => (clone $baseQuery)->count(),
            'active'   => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Admin\Plans;

use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\DB;

final class SavePlanAction
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Plan
    {
        return DB::transaction(fn (): Plan => Plan::query()->create($data));
    }

    /** @param array<string, mixed> $data */
    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data): Plan {
            $plan->update($data);

            return $plan->refresh();
        });
    }
}

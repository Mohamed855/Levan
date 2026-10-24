<?php

declare(strict_types=1);

namespace App\Actions\Admin\Plans;

use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReorderPlansAction
{
    /** @param array<int|string, int|string> $order */
    public function execute(array $order): void
    {
        DB::transaction(function () use ($order): void {
            $plans = Plan::query()->orderBy('id')->lockForUpdate()->get();
            $submittedIds = array_map('intval', array_keys($order));
            $actualIds = $plans->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

            sort($submittedIds);
            sort($actualIds);
            if ($submittedIds !== $actualIds) {
                throw ValidationException::withMessages(['order' => 'تغيّرت قائمة الباقات؛ أعد تحميل الصفحة قبل حفظ الترتيب.']);
            }

            foreach ($plans as $plan) {
                $plan->update(['sort_order' => (int) $order[$plan->getKey()]]);
            }
        });
    }
}

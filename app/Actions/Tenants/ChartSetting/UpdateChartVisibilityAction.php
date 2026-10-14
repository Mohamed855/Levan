<?php

declare(strict_types=1);

namespace App\Actions\Tenants\ChartSetting;

use App\Models\Tenants\Settings\Settings;
use Illuminate\Support\Facades\DB;

final class UpdateChartVisibilityAction
{
    public function execute(string $key, bool $isVisible): void
    {
        DB::transaction(function () use ($key, $isVisible): void {
            Settings::query()->firstOrFail()->update([$key => $isVisible]);
        });
    }
}

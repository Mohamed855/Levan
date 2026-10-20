<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\ChartSetting;

use App\Actions\Tenants\ChartSetting\UpdateChartVisibilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\ChartSetting\UpdateChartVisibilityRequest;
use Illuminate\Http\JsonResponse;

final class ChartSettingController extends Controller
{
    public function __construct(private readonly UpdateChartVisibilityAction $updateChartVisibility) {}

    public function updateChartVisibility(UpdateChartVisibilityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isVisible = (bool) $data['value'];
        $this->updateChartVisibility->execute($data['key'], $isVisible);

        return response()->json([
            'success' => true,
            'message' => 'تم التحديث بنجاح',
            'data' => ['key' => $data['key'], 'value' => $isVisible],
        ]);
    }
}

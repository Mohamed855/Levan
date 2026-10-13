<?php

namespace App\Http\Controllers\Tenants\ChartSetting;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Settings\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartSettingController extends Controller
{
    private const ALLOWED_KEYS = ['compare_effort', 'daily_tasks', 'Overdue_tasks'];

    public function updateChartVisibility(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', Rule::in(self::ALLOWED_KEYS)],
            'value' => ['required', 'boolean'],
        ]);

        $settings = Settings::firstOrFail();
        $settings->update([$data['key'] => $data['value']]);

        return response()->json([
            'success' => true,
            'message' => 'تم التحديث بنجاح',
            'data' => [
                'key' => $data['key'],
                'value' => $data['value'],
            ],
        ]);
    }
}

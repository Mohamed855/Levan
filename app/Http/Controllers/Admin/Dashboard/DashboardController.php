<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $dashboardService = new DashboardService;
        $data = $dashboardService->getDashboardData();

        return view('admin.dashboard.index', $data);
    }

    /**
     * API endpoint: جلب بيانات استخدام المنصة حسب المدة
     * GET /admin/dashboard/platform-activity?range=1month|3months|6months|1year
     */
    public function platformActivity()
    {
        $range = request()->input('range', '1month');
        $allowed = ['1month', '3months', '6months', '1year'];

        if (! in_array($range, $allowed)) {
            $range = '1month';
        }

        $service = new DashboardService;
        $data = $service->getPlatformActivityByRange($range);

        return response()->json(['data' => $data]);
    }
}

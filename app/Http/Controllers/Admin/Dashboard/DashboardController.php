<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\PlatformActivityRequest;
use App\Services\Admin\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
        $this->middleware('can:dashboard.main');
    }

    public function dashboard()
    {
        $data = $this->dashboardService->getDashboardData();

        return view('admin.dashboard.index', $data);
    }

    /**
     * API endpoint: جلب بيانات استخدام المنصة حسب المدة
     * GET /admin/dashboard/platform-activity?range=1month|3months|6months|1year
     */
    public function platformActivity(PlatformActivityRequest $request)
    {
        $range = $request->validated('range') ?? '1month';
        $data = $this->dashboardService->getPlatformActivityByRange($range);

        return response()->json(['data' => $data]);
    }
}

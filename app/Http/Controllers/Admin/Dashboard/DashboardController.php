<?php

namespace App\Http\Controllers\Admin\Dashboard;


use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{

    public function dashboard()
    {
        $dashboardService = new DashboardService();
        $data = $dashboardService->getDashboardData();

        return view('admin.dashboard.index', $data);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Tenants\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
        $this->middleware('can:dashboard.view');
    }

    public function index(Request $request): Response
    {
        $data = $this->dashboardService->getDashboardData();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'kpis' => $data['kpis'],
                'stats' => $data['kpis'],
            ]);
        }

        return response()->view('tenant.dashboard.index', $data);
    }

    /**
     * API endpoint: جلب نشاط المزادات والمبيعات حسب النطاق الزمني
     */
    public function auctionActivity(Request $request): JsonResponse
    {
        $range = (string) $request->get('range', '1month');
        $data = $this->dashboardService->getAuctionActivityByRange($range);

        return response()->json(['data' => $data]);
    }

    public function getStatisticsData(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'assignments_count' => \App\Models\Tenants\Assignments\Assignment::query()->count(),
                'properties_count' => \App\Models\Tenants\Properties\Property::query()->count(),
                'auctions_count' => \App\Models\Tenants\Auctions\Auction::query()->count(),
            ],
        ]);
    }
}

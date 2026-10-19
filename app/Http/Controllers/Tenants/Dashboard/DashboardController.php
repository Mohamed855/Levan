<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Tenants\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
        $this->middleware('can:dashboard.view');
    }

    public function index(Request $request): Response
    {
        $data = $this->dashboardService->getFoundationDashboardData();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'stats' => $data['stats']]);
        }

        return response()->view('tenants.dashboard.index', $data);
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

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

    public function getUpcomingFollowUps(Request $request): JsonResponse
    {
        return DataTables::of($this->dashboardService->getUpcomingFollowUpsQuery($request->only([
            'company_id', 'portfolio_id', 'relationship_manager_id',
        ])))->addIndexColumn()->toJson();
    }

    public function getMyUpcomingFollowUps(Request $request): JsonResponse
    {
        return DataTables::of($this->dashboardService->getUpcomingFollowUpsQuery(
            $request->only(['company_id', 'portfolio_id']),
            (int) $request->user('tenant')->getAuthIdentifier()
        ))->toJson();
    }

    public function exportCollectionSessions(Request $request): Response
    {
        $rows = $this->dashboardService->getCollectionSessionsData(
            $request->string('date')->toString() ?: null,
            $request->string('status')->toString() ?: null
        );

        return $this->csvResponse('collection-sessions.csv', $rows);
    }

    public function excellence(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getExcellenceDashboardData($request->string('period', 'month')->toString()),
        ]);
    }

    public function getMyPerformanceComparison(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getUserComparisonData(
                (int) $request->user('tenant')->getAuthIdentifier(),
                $request->string('period', 'month')->toString(),
                $request->string('data_type', 'collections')->toString()
            ),
        ]);
    }

    public function getStatisticsData(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->dashboardService->getStatisticsData(
            $request->string('period', 'month')->toString()
        )]);
    }

    public function getApprovalsData(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->dashboardService->getApprovalsData(
            $request->string('period', 'month')->toString()
        )]);
    }

    public function getOverdueTasks(Request $request): JsonResponse
    {
        return DataTables::of($this->dashboardService->getOverdueTasksQuery(null, $request->only([
            'company_id', 'portfolio_id', 'relationship_manager_id', 'result_type', 'overdue_days', 'date_from', 'date_to',
        ])))->addIndexColumn()->toJson();
    }

    public function exportOverdueTasks(Request $request): Response
    {
        $rows = $this->dashboardService->getOverdueTasksQuery(null, $request->only([
            'company_id', 'portfolio_id', 'relationship_manager_id', 'result_type', 'overdue_days', 'date_from', 'date_to',
        ]))->get()->toArray();

        return $this->csvResponse('overdue-tasks.csv', $rows);
    }

    public function getMyOverdueTasks(Request $request): JsonResponse
    {
        return DataTables::of($this->dashboardService->getOverdueTasksQuery(
            (int) $request->user('tenant')->getAuthIdentifier(),
            $request->only(['result_type', 'overdue_days', 'date_from', 'date_to'])
        ))->toJson();
    }

    public function exportMyOverdueTasks(Request $request): Response
    {
        $rows = $this->dashboardService->getOverdueTasksQuery(
            (int) $request->user('tenant')->getAuthIdentifier(),
            $request->only(['result_type', 'overdue_days', 'date_from', 'date_to'])
        )->get()->toArray();

        return $this->csvResponse('my-overdue-tasks.csv', $rows);
    }

    private function csvResponse(string $fileName, array $rows): Response
    {
        return response()->streamDownload(static function () use ($rows): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            if ($rows !== []) {
                fputcsv($stream, array_keys((array) $rows[0]));
                foreach ($rows as $row) {
                    fputcsv($stream, array_map(static fn ($value): string => is_scalar($value) || $value === null
                        ? (string) $value
                        : json_encode($value, JSON_UNESCAPED_UNICODE), (array) $row));
                }
            }
            fclose($stream);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

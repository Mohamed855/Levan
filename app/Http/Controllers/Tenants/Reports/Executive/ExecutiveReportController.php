<?php

namespace App\Http\Controllers\Tenants\Reports\Executive;

use App\Exports\Tenants\Reports\Executive\ExecutiveReportExport;
use App\Http\Controllers\Controller;
use App\Services\Tenants\Reports\Executive\ExecutiveReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExecutiveReportController extends Controller
{
    public function __construct(private readonly ExecutiveReportService $service)
    {
        $this->middleware('can:التقارير التنفيذية');
    }

    public function index()
    {
        return view('tenants.reports.executive.index', [
            ...$this->service->getCount(),
            ...$this->service->getFormData(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->service->getDashboardData($this->parseFilters($request)));
    }

    public function exportExcel(Request $request)
    {
        $filters  = $this->parseFilters($request);
        $sections = $request->filled('sections')
            ? array_filter(explode(',', $request->sections))
            : [];

        if ($request->filled('export_limit')) {
            $filters['export_limit'] = (int) $request->export_limit;
        }

        $data     = $this->service->getDashboardData($filters);
        $filename = 'التقارير_التنفيذية_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ExecutiveReportExport($data, $filters, $sections), $filename);
    }

    private function parseFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $filters['company_id'] = $request->company_id;
        }

        if ($request->filled('portfolio_id') && $request->portfolio_id !== 'all') {
            $filters['portfolio_id'] = $request->portfolio_id;
        }

        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date_to;
        }

        return $filters;
    }
}

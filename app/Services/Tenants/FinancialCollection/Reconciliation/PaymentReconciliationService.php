<?php

namespace App\Services\Tenants\FinancialCollection\Reconciliation;

use App\Exports\Tenants\FinancialCollection\Reconciliation\SummaryReportExport;
use App\Exports\Tenants\FinancialCollection\Reconciliation\UnmatchedReportExport;
use App\Exports\Tenants\FinancialCollection\Reconciliation\DiscrepanciesReportExport;
use App\Exports\Tenants\FinancialCollection\Reconciliation\SystemOnlyReportExport;
use App\Exports\Tenants\FinancialCollection\Reconciliation\EmployeePerformanceReportExport;
use App\Exports\Tenants\FinancialCollection\Reconciliation\ErrorsReportExport;
use App\Jobs\Tenants\FinancialCollection\Reconciliation\ProcessPaymentReconciliation;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliation;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PaymentReconciliationService
{
    /*
    |--------------------------------------------------------------------------
    | Get Initial View Data
    |--------------------------------------------------------------------------
    */
    public function getInitialViewData(): array
    {
        return [
            'companies' => Company::active()->get(['id', 'name']),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolios For Company
    |--------------------------------------------------------------------------
    */
    public function getPortfoliosForCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Portfolio::where('company_id', $companyId)
            ->active()
            ->get(['id', 'name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Analyze File Headers
    |--------------------------------------------------------------------------
    */
    public function analyzeFileHeaders(UploadedFile $file): array
    {
        $tempPath = $file->store('temp_analysis');
        $fullPath = storage_path('app/' . $tempPath);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $firstRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];

            $headers = array_map(fn($h) => trim($h ?? ''), $firstRow);
            $headers = array_filter($headers, fn($h) => !empty($h));

            Storage::delete($tempPath);

            return array_values($headers);
        } catch (\Exception $e) {
            Storage::delete($tempPath);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store Temporary File
    |--------------------------------------------------------------------------
    */
    public function storeTemporaryFile(UploadedFile $file): array
    {
        $validation = $this->validateFileRowCount($file);

        if (!$validation['valid']) {
            throw new \Exception($validation['message']);
        }

        $tempPath = $file->store('temp_reconciliations');

        return [
            'temp_file_path' => $tempPath,
            'total_rows' => $validation['total_rows']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate File Row Count
    |--------------------------------------------------------------------------
    */
    public function validateFileRowCount(UploadedFile $file): array
    {
        $tempPath = $file->store('temp_analysis');
        $fullPath = storage_path('app/' . $tempPath);

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $totalRows = $sheet->getHighestRow() - 1;

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet, $reader);
            gc_collect_cycles();

            Storage::delete($tempPath);

            if ($totalRows > 50000) {
                return [
                    'valid' => false,
                    'total_rows' => $totalRows,
                    'message' => sprintf(
                        'عدد الصفوف (%s) يتجاوز الحد الأقصى المسموح به (50,000 صف). يرجى تقسيم الملف.',
                        number_format($totalRows)
                    )
                ];
            }

            return [
                'valid' => true,
                'total_rows' => $totalRows
            ];
        } catch (\Exception $e) {
            Storage::delete($tempPath);
            return [
                'valid' => false,
                'message' => 'فشل في التحقق من عدد الصفوف: ' . $e->getMessage()
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Reconciliation Process
    |--------------------------------------------------------------------------
    */
    public function queueReconciliationProcess(array $data, array $fieldMappings): string
    {
        $jobId = uniqid('reconciliation_');

        Cache::put("reconciliation_progress_{$jobId}", [
            'status' => 'queued',
            'processed' => 0,
            'total' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'discrepancies' => 0,
            'errors' => 0,
            'started_at' => now(),
        ], 3600);

        ProcessPaymentReconciliation::dispatch([
            'job_id' => $jobId,
            'temp_file_path' => $data['temp_file_path'],
            'company_id' => $data['company_id'],
            'portfolio_id' => $data['portfolio_id'],
            'reconciliation_month' => $data['reconciliation_month'],
            'field_mappings' => $fieldMappings,
            'user_id' => auth()->id(),
        ]);

        return $jobId;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Progress Status
    |--------------------------------------------------------------------------
    */
    public function getProgressStatus(string $jobId): array
    {
        return Cache::get("reconciliation_progress_{$jobId}", [
            'status' => 'not_found',
            'processed' => 0,
            'total' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'discrepancies' => 0,
            'errors' => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Summary Report
    |--------------------------------------------------------------------------
    */
    public function generateSummaryReport(PaymentReconciliation $reconciliation): string
    {
        $fileName = 'summary_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        Excel::store(new SummaryReportExport($reconciliation), $filePath, 'tenant_public');

        return $filePath;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unmatched Report
    |--------------------------------------------------------------------------
    */
    public function generateUnmatchedReport(PaymentReconciliation $reconciliation): string
    {
        $items = $reconciliation->items()->unmatched()->with('customer.relationshipManager')->get();
        $fileName = 'unmatched_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        Excel::store(new UnmatchedReportExport($items), $filePath, 'tenant_public');

        return $filePath;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Discrepancies Report
    |--------------------------------------------------------------------------
    */
    public function generateDiscrepanciesReport(PaymentReconciliation $reconciliation): string
    {
        $items = $reconciliation->items()->discrepancy()->with('customer')->get();
        $fileName = 'discrepancies_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        Excel::store(new DiscrepanciesReportExport($items), $filePath, 'tenant_public');

        return $filePath;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate System Only Report
    |--------------------------------------------------------------------------
    */
    public function generateSystemOnlyReport(PaymentReconciliation $reconciliation): string
    {
        $collections = Collection::query()
            ->whereHas('customer', function ($q) use ($reconciliation) {
                $q->where('portfolio_id', $reconciliation->portfolio_id);
            })
            ->whereYear('collection_date', $reconciliation->reconciliation_month->year)
            ->whereMonth('collection_date', $reconciliation->reconciliation_month->month)
            ->where('is_verified', false)
            ->where('status', 'approved')
            ->with(['customer', 'addedBy'])
            ->get();

        $fileName = 'system_only_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        Excel::store(new SystemOnlyReportExport($collections), $filePath, 'tenant_public');

        return $filePath;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Employee Performance Report
    |--------------------------------------------------------------------------
    */
    public function generateEmployeePerformanceReport(PaymentReconciliation $reconciliation): string
    {
        $stats = $reconciliation->items()
            ->whereNotNull('customer_id')
            ->with('customer.relationshipManager')
            ->get()
            ->groupBy(fn($item) => $item->customer?->relationship_manager_id)
            ->map(function ($items, $managerId) {
                $matched = $items->where('match_status', 'matched')->count();
                $unmatched = $items->where('match_status', 'unmatched')->count();
                $total = $matched + $unmatched;

                return [
                    'manager' => $items->first()?->customer?->relationshipManager?->name ?? 'غير محدد',
                    'matched' => $matched,
                    'unmatched' => $unmatched,
                    'total' => $total,
                    'percentage' => $total > 0 ? round(($matched / $total) * 100, 1) : 0
                ];
            })
            ->values()
            ->toArray();

        $fileName = 'employee_performance_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        Excel::store(new EmployeePerformanceReportExport($stats), $filePath, 'tenant_public');

        return $filePath;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Errors Report
    |--------------------------------------------------------------------------
    */
    public function generateErrorsReport(PaymentReconciliation $reconciliation): string
    {
        $items = $reconciliation->items()->errors()->get();
        $fileName = 'errors_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        Excel::store(new ErrorsReportExport($items), $filePath, 'tenant_public');

        return $filePath;
    }
}

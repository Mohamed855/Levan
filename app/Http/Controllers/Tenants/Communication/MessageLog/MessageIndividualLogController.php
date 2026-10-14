<?php

namespace App\Http\Controllers\Tenants\Communication\MessageLog;

use App\DataTables\Tenants\Communication\MessageLog\MessageIndividualLogsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Communication\ExportMessageIndividualLogsRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Services\Tenants\Communication\MessageLog\MessageIndividualLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageIndividualLogController extends Controller
{
    protected MessageIndividualLogService $messageLogService;

    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */
    public function __construct(MessageIndividualLogService $messageLogService)
    {
        $this->messageLogService = $messageLogService;
        $this->middleware('auth');
        $this->authorizeResource(MessageIndividualLog::class, 'messageIndividualLog');
        $this->middleware('can:message_logs.view')->except(['show', 'destroy', 'export']);
        $this->middleware('can:message_logs.show')->only(['show']);
        $this->middleware('can:message_logs.delete')->only(['destroy']);
        $this->middleware('can:export,'.MessageIndividualLog::class)->only(['export']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Logs List
    |--------------------------------------------------------------------------
    | Display a listing of message individual logs with statistics.
    */
    public function index(MessageIndividualLogsDataTable $dataTable)
    {
        $filters = request()->only([
            'status',
            'message_type',
            'company_id',
            'portfolio_id',
            'template_id',
            'date_from',
            'date_to',
        ]);

        $statistics = $this->messageLogService->getStatistics($filters);
        $formData = $this->messageLogService->getFormData();

        return $dataTable->render('tenants.communication.message_individual_logs.index', [
            'statistics' => $statistics,
            'formData' => $formData,
            'filters' => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Log Details
    |--------------------------------------------------------------------------
    | Display the specified message individual log details.
    */
    public function show(MessageIndividualLog $messageIndividualLog): View
    {
        $log = $this->messageLogService->getLogWithDetails($messageIndividualLog->id);

        return view('tenants.communication.message_individual_logs.show', [
            'log' => $log,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Log
    |--------------------------------------------------------------------------
    | Remove the specified log from storage.
    */
    public function destroy(MessageIndividualLog $messageIndividualLog): RedirectResponse
    {
        try {
            $this->messageLogService->deleteLog($messageIndividualLog);

            return redirect()
                ->back()
                ->with('success', 'تم حذف سجل الرسالة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف سجل الرسالة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard Statistics
    |--------------------------------------------------------------------------
    | Get statistics data for dashboard widgets.
    */
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'message_type',
                'company_id',
                'portfolio_id',
                'date_from',
                'date_to',
            ]);

            $statistics = $this->messageLogService->getStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Data
    |--------------------------------------------------------------------------
    | Get chart data for dashboard visualization.
    */
    public function chartData(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'message_type',
                'company_id',
                'portfolio_id',
                'date_from',
                'date_to',
            ]);

            $chartData = $this->messageLogService->getChartData($filters);

            return response()->json([
                'success' => true,
                'data' => $chartData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الرسوم البيانية',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Export Logs
    |--------------------------------------------------------------------------
    | Export filtered logs to Excel format.
    */
    public function export(ExportMessageIndividualLogsRequest $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $filters = $request->validated();

            $hasFilters = ! empty($filters['status']) ||
                ! empty($filters['message_type']) ||
                ! empty($filters['company_id']) ||
                ! empty($filters['portfolio_id']) ||
                ! empty($filters['template_id']) ||
                ! empty($filters['customer_id']) ||
                ! empty($filters['date_from']) ||
                ! empty($filters['date_to']) ||
                ! empty($filters['search']);

            if (! $hasFilters) {
                return redirect()
                    ->back()
                    ->with('warning', 'يرجى تطبيق فلتر واحد على الأقل قبل التصدير');
            }

            return $this->messageLogService->exportLogs($filters);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء تصدير البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolios by Company
    |--------------------------------------------------------------------------
    | Return portfolios filtered by company for AJAX requests.
    */
    public function getPortfoliosByCompany(Request $request): JsonResponse
    {
        try {
            $companyId = $request->route('companyId');

            $portfolios = Portfolio::where('company_id', $companyId)
                ->where('status', 'active')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $portfolios,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المحافظ',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customers by Portfolio
    |--------------------------------------------------------------------------
    | Return customers filtered by portfolio for AJAX requests.
    */
    public function getCustomersByPortfolio(Request $request): JsonResponse
    {
        try {
            $portfolioId = $request->route('portfolioId');

            $customers = Customer::where('portfolio_id', $portfolioId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العملاء',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | عرض أرشيف الملفات المصدرة
    |--------------------------------------------------------------------------
    */
    public function archives(): View
    {
        $disk = Storage::disk('tenant_public');
        $files = $disk->files('message-logs');

        $archives = collect($files)->map(function ($file) use ($disk) {
            $lastModified = $disk->lastModified($file);

            return [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes($disk->size($file)),
                'size_bytes' => $disk->size($file), // ← إضافة الحجم بالبايت
                'date' => Carbon::createFromTimestamp($lastModified)->format('Y-m-d H:i'),
                'period' => Carbon::createFromTimestamp($lastModified)->locale('ar')->translatedFormat('F Y'),
            ];
        })->sortByDesc('date')->values();

        // حساب الحجم الإجمالي
        $totalSize = $this->calculateTotalSize($archives);

        return view('tenants.communication.message_individual_logs.archives', compact('archives', 'totalSize'));
    }

    /*
    |--------------------------------------------------------------------------
    | حساب الحجم الإجمالي
    |--------------------------------------------------------------------------
    */
    private function calculateTotalSize($archives): string
    {
        $totalBytes = 0;

        foreach ($archives as $archive) {
            $totalBytes += $archive['size_bytes'];
        }

        return $this->formatBytes($totalBytes);
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل ملف أرشيف
    |--------------------------------------------------------------------------
    */
    public function downloadFile($file): StreamedResponse
    {
        $filePath = 'message-logs/'.$file;
        $disk = Storage::disk('tenant_public');

        if (! $disk->exists($filePath)) {
            abort(404, 'الملف غير موجود');
        }

        return $disk->download($filePath);
    }

    /*
    |--------------------------------------------------------------------------
    | تنسيق حجم الملف
    |--------------------------------------------------------------------------
    */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}

<?php

namespace App\Services\Tenants\Reports\Financial;

use App\Exports\Tenants\Reports\Financial\FinancialReportExport;
use App\Jobs\Tenants\Reports\Financial\ExportFinancialCollectionsJob;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Reports\TenantExportAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancialReportService
{
    /*
    |--------------------------------------------------------------------------
    | Add Constants
    |--------------------------------------------------------------------------
    */
    private const LARGE_EXPORT_THRESHOLD = 5000;

    private const CHUNK_SIZE = 1000;

    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات تقرير التحصيل المالي
    |--------------------------------------------------------------------------
    */
    public function getReportStatistics(): array
    {
        $totalCollections = Collection::whereHas('customer')->count();

        $approvedCollections = Collection::where('status', 'approved')
            ->whereHas('customer')
            ->count();

        $pendingCollections = Collection::where('status', 'pending')
            ->whereHas('customer')
            ->count();

        $rejectedCollections = Collection::where('status', 'rejected')
            ->whereHas('customer')
            ->count();

        $totalApprovedAmount = Collection::where('status', 'approved')
            ->whereHas('customer')
            ->sum('collected_amount');

        $totalPendingAmount = Collection::where('status', 'pending')
            ->whereHas('customer')
            ->sum('collected_amount');

        $recentCollections = Collection::where('created_at', '>=', now()->subDays(30))
            ->whereHas('customer')
            ->count();

        $collectionsByStatus = [
            'approved' => $approvedCollections,
            'pending' => $pendingCollections,
            'rejected' => $rejectedCollections,
        ];

        $collectionsByUser = Collection::with('addedBy')
            ->where('status', 'approved')
            ->whereNotNull('added_by')
            ->whereHas('customer')
            ->get()
            ->groupBy('addedBy.name')
            ->map(function ($group) {
                return $group->sum('collected_amount');
            })
            ->sortDesc()
            ->take(5)
            ->toArray();

        $collectionsByMonth = Collection::where('created_at', '>=', now()->subMonths(6))
            ->whereHas('customer')
            ->get()
            ->groupBy(function ($collection) {
                return Carbon::parse($collection->created_at)->locale('ar')->translatedFormat('F Y');
            })
            ->map->count()
            ->toArray();

        return [
            'total_collections' => $totalCollections,
            'approved_collections' => $approvedCollections,
            'pending_collections' => $pendingCollections,
            'rejected_collections' => $rejectedCollections,
            'total_approved_amount' => $totalApprovedAmount,
            'total_pending_amount' => $totalPendingAmount,
            'recent_collections' => $recentCollections,
            'collections_by_status' => $collectionsByStatus,
            'collections_by_user' => $collectionsByUser,
            'collections_by_month' => $collectionsByMonth,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على بيانات النموذج
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        return [
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'customers' => $this->getInitialCustomers(),
            'companies' => Company::select('id', 'name')->orderBy('name')->get(),
            'portfolios' => Portfolio::select('id', 'name', 'company_id')->orderBy('name')->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على عدد التحصيلات حسب الفلاتر
    |--------------------------------------------------------------------------
    */
    public function getTransactionsCount(array $filters): int
    {
        try {
            $query = $this->buildFilteredQuery($filters);

            return $query->count();
        } catch (\Exception $e) {
            Log::error('Error in getTransactionsCount: '.$e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير التحصيل المالي
    |--------------------------------------------------------------------------
    */
    public function exportFinancialReport(array $filters): BinaryFileResponse
    {
        $query = $this->buildFilteredQuery($filters);
        $collections = $query->orderBy('collections.created_at')->get();

        $fileName = 'financial_collections_report_'.now()->format('Y_m_d_H_i_s').'.xlsx';

        return Excel::download(new FinancialReportExport($collections, $filters), $fileName);
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير التحصيل المالي إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportFinancialReportToPDF(array $filters): BinaryFileResponse
    {
        try {
            $query = $this->buildFilteredQuery($filters);
            $collections = $query->orderBy('collections.created_at')->get();
            $totalCollections = $collections->count();

            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;
            $dateRange = $this->getDateRangeText($dateFrom, $dateTo);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 10,
                'margin_header' => 0,
                'margin_footer' => 0,
                'default_font' => 'almarai',
                'fontDir' => array_merge(
                    (new ConfigVariables)->getDefaults()['fontDir'],
                    [public_path('static/fonts/almarai')]
                ),
                'fontdata' => array_merge(
                    (new FontVariables)->getDefaults()['fontdata'],
                    ['almarai' => [
                        'R' => 'Almarai-Regular.ttf',
                        'B' => 'Almarai-Bold.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ]]
                ),
                'default_font_size' => 12,
                'tempDir' => storage_path('app/public/temp'),
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->SetHTMLHeader('');
            $mpdf->SetTopMargin(0);

            $appliedFilters = $this->getAppliedFiltersText($filters);

            $html = $this->generateFinancialReportHTML(
                $collections,
                $totalCollections,
                $dateRange,
                $appliedFilters
            );

            $mpdf->WriteHTML($html);

            $fileName = 'تقرير_التحصيلات_المالية_'.now()->format('Y_m_d_H_i_s').'.pdf';

            $tempDir = storage_path('app/public/temp');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir.'/'.$fileName;
            $mpdf->Output($tempPath, 'F');

            return response()->download($tempPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Log::error('exportFinancialReportToPDF error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الحقول المتاحة للتصدير
    |--------------------------------------------------------------------------
    */
    public function getAvailableExportFields(): array
    {
        return [
            'fields' => [
                // ['key' => 'id', 'label' => 'رقم التحصيل', 'selected' => false],
                ['key' => 'customer_name', 'label' => 'اسم العميل', 'selected' => true],
                ['key' => 'customer_account', 'label' => 'رقم حساب العميل', 'selected' => true],
                ['key' => 'customer_identity_number', 'label' => 'رقم هوية العميل', 'selected' => false],
                ['key' => 'company_name', 'label' => 'الشركة', 'selected' => false],
                ['key' => 'portfolio_name', 'label' => 'المحفظة', 'selected' => false],
                ['key' => 'collected_amount', 'label' => 'المبلغ المحصل', 'selected' => true],
                ['key' => 'payment_method', 'label' => 'طريقة الدفع', 'selected' => true],
                ['key' => 'receipt_number', 'label' => 'رقم السند', 'selected' => true],
                ['key' => 'status', 'label' => 'الحالة', 'selected' => true],
                ['key' => 'collection_type', 'label' => 'نوع التحصيل', 'selected' => false],
                ['key' => 'payment_type', 'label' => 'نوع السداد', 'selected' => false],
                ['key' => 'collection_date', 'label' => 'تاريخ التحصيل', 'selected' => true],
                ['key' => 'created_by', 'label' => 'أنشأ بواسطة', 'selected' => false],
                ['key' => 'created_at', 'label' => 'تاريخ الإنشاء', 'selected' => true],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير التحصيل المالي إلى Excel مع الحقول المخصصة
    |--------------------------------------------------------------------------
    */
    public function exportFilteredFinancialToExcel(array $filters, array $selectedFields = []): string
    {
        $query = $this->buildFilteredQuery($filters);

        $collections = $query->with([
            'customer',
            'addedBy',
            'paymentMethod',
        ])->orderBy('collections.created_at')->get();

        $fileName = 'تقرير_التحصيلات_المالية_مخصص_'.now()->format('Y_m_d_H_i_s').'.xlsx';
        Excel::store(
            new FinancialReportExport($collections, $filters, $selectedFields),
            'exports/'.$fileName,
            'tenant_public'
        );
        app(TenantExportAccess::class)->registerFile($fileName, (int) auth('tenant')->id());

        return $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | بناء الاستعلام مع الفلاتر
    |--------------------------------------------------------------------------
    */
    private function buildFilteredQuery(array $filters)
    {
        $query = Collection::with([
            'customer:id,name,account_number,identity_number,company_id,portfolio_id',
            'customer.company:id,name',
            'customer.portfolio:id,name',
            'addedBy:id,name',
            'paymentMethod:id,name',
        ])
            ->whereHas('customer')
            ->select('collections.*');

        $this->applyFiltersToQuery($query, $filters);

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر على الاستعلام
    |--------------------------------------------------------------------------
    */
    private function applyFiltersToQuery($query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($key) {
                case 'company_id':
                    $query->whereHas('customer', function ($q) use ($value) {
                        $q->where('company_id', $value);
                    });
                    break;
                case 'portfolio_id':
                    $query->whereHas('customer', function ($q) use ($value) {
                        $q->where('portfolio_id', $value);
                    });
                    break;
                case 'date_from':
                    $query->whereDate('collections.collection_date', '>=', $value);
                    break;
                case 'date_to':
                    $query->whereDate('collections.collection_date', '<=', $value);
                    break;
                case 'created_from':
                    $query->whereDate('collections.created_at', '>=', $value);
                    break;
                case 'created_to':
                    $query->whereDate('collections.created_at', '<=', $value);
                    break;
                case 'customer_id':
                    $query->where('customer_id', $value);
                    break;
                case 'status':
                    $query->where('collections.status', $value);
                    break;
                case 'created_by':
                    $query->where('added_by', $value);
                    break;
                case 'amount_from':
                    $query->where('collected_amount', '>=', $value);
                    break;
                case 'amount_to':
                    $query->where('collected_amount', '<=', $value);
                    break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على نص نطاق التاريخ
    |--------------------------------------------------------------------------
    */
    private function getDateRangeText(?string $dateFrom, ?string $dateTo): string
    {
        if ($dateFrom && $dateTo) {
            return "من {$dateFrom} إلى {$dateTo}";
        } elseif ($dateFrom) {
            return "من {$dateFrom}";
        } elseif ($dateTo) {
            return "حتى {$dateTo}";
        }

        return 'جميع الفترات';
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على نص الفلاتر المطبقة
    |--------------------------------------------------------------------------
    */
    private function getAppliedFiltersText(array $filters): string
    {
        $appliedFilters = [];

        if (! empty($filters['customer_id'])) {
            $customer = Customer::find($filters['customer_id']);
            if ($customer) {
                $appliedFilters[] = "العميل: {$customer->name}";
            }
        }

        if (! empty($filters['status'])) {
            $statusLabel = match ($filters['status']) {
                'approved' => 'معتمد',
                'pending' => 'معلق',
                'rejected' => 'مرفوض',
                default => $filters['status']
            };
            $appliedFilters[] = "الحالة: {$statusLabel}";
        }

        if (! empty($filters['created_by'])) {
            $user = User::find($filters['created_by']);
            if ($user) {
                $appliedFilters[] = "أنشأ بواسطة: {$user->name}";
            }
        }

        if (! empty($filters['amount_from']) || ! empty($filters['amount_to'])) {
            $amountFrom = $filters['amount_from'] ?? '0';
            $amountTo = $filters['amount_to'] ?? '∞';
            $appliedFilters[] = "المبلغ: من {$amountFrom} إلى {$amountTo}";
        }

        return empty($appliedFilters) ? 'لا توجد فلاتر مطبقة' : implode(' | ', $appliedFilters);
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء HTML للتقرير
    |--------------------------------------------------------------------------
    */
    private function generateFinancialReportHTML(
        $collections,
        int $totalCollections,
        string $dateRange,
        string $appliedFilters
    ): string {
        return view('tenants.reports.financial.pdf', compact(
            'collections',
            'totalCollections',
            'dateRange',
            'appliedFilters'
        ))->render();
    }

    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    */
    public function searchCustomers(?string $search = '', int $limit = 20): array
    {
        $query = Customer::select('id', 'name', 'account_number', 'phone');

        if (auth()->user()->can('customers.view')) {
        } elseif (auth()->user()->can('customers.my_view')) {
            $query->where('relationship_manager_id', auth()->id());
        } else {
            return [];
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('account_number')
            ->limit($limit)
            ->get()
            ->unique('account_number')
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->account_number,
                ];
            })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer ById
    |--------------------------------------------------------------------------
    */
    public function getCustomerById(int $customerId): ?array
    {
        $customer = Customer::select('id', 'name', 'account_number')
            ->find($customerId);

        if (! $customer) {
            return null;
        }

        if (
            ! auth()->user()->can('customers.view') &&
            auth()->user()->can('customers.my_view') &&
            $customer->relationship_manager_id !== auth()->id()
        ) {
            return null;
        }

        return [
            'id' => $customer->id,
            'text' => $customer->account_number,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Initial Customers
    |--------------------------------------------------------------------------
    */
    public function getInitialCustomers(int $limit = 10): array
    {
        $query = Customer::select('id', 'name', 'account_number', 'phone');

        if (! auth()->user()->can('customers.view') && auth()->user()->can('customers.my_view')) {
            $query->where('relationship_manager_id', auth()->id());
        } elseif (! auth()->user()->can('customers.view')) {
            return [];
        }

        return $query->orderBy('account_number')
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->account_number,
                ];
            })->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Start Async Export
    |--------------------------------------------------------------------------
    */
    public function startAsyncExport(array $filters, array $selectedFields, int $userId): array
    {
        $count = $this->getTransactionsCount($filters);

        if ($count === 0) {
            return [
                'success' => false,
                'message' => 'لا توجد بيانات للتصدير',
                'count' => 0,
            ];
        }

        $job = new ExportFinancialCollectionsJob($filters, $selectedFields, $userId);
        app(TenantExportAccess::class)->registerExport($job->getExportId(), (int) $userId);
        dispatch($job);

        return [
            'success' => true,
            'export_id' => $job->getExportId(),
            'count' => $count,
            'message' => $count > self::LARGE_EXPORT_THRESHOLD
                ? "سيتم تصدير {$count} تحصيلة  سيتم إشعارك عند الانتهاء"
                : "جاري تصدير {$count} تحصيلة...",
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export Progress
    |--------------------------------------------------------------------------
    */
    public function getExportProgress(string $exportId): array
    {
        app(TenantExportAccess::class)->authorizeExport($exportId, auth('tenant')->id());

        $progress = Cache::get("financial_export_progress_{$exportId}");
        $result = Cache::get("financial_export_result_{$exportId}");

        if ($result) {
            return array_merge(['export_id' => $exportId], $result);
        }

        if ($progress) {
            return array_merge(['export_id' => $exportId, 'status' => 'processing'], $progress);
        }

        return [
            'export_id' => $exportId,
            'status' => 'not_found',
            'message' => 'لم يتم العثور على عملية التصدير',
        ];
    }
}

<?php

namespace App\Services\Tenants\Reports\CustomerFollow;

use App\Exports\Tenants\Reports\CustomerFollow\CustomerFollowReportExport;
use App\Jobs\Tenants\Reports\CustomerFollow\ExportCustomerFollowsJob;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CustomerFollowReportService
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
    | الحصول على إحصائيات تقرير المتابعات
    |--------------------------------------------------------------------------
    */
    public function getReportStatistics(): array
    {
        try {
            // ✅ 1. الإحصائيات الرئيسية
            $stats = DB::selectOne("
            SELECT 
                COUNT(*) as total_follows,
                SUM(CASE WHEN cf.next_contact_datetime > NOW() THEN 1 ELSE 0 END) as upcoming_follows,
                SUM(CASE WHEN cf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_follows
            FROM customer_follows cf
            INNER JOIN customers c ON cf.customer_id = c.id
            WHERE c.deleted_at IS NULL
        ");

            // ✅ 2. طرق الاتصال - اسم الجدول الصحيح
            $contactMethods = DB::select("
            SELECT scm.name, COUNT(*) as total
            FROM customer_follows cf
            INNER JOIN customers c ON cf.customer_id = c.id
            INNER JOIN settings_contact_methods scm ON cf.contact_method_id = scm.id
            WHERE c.deleted_at IS NULL 
            AND cf.contact_method_id IS NOT NULL
            GROUP BY scm.name
            ORDER BY total DESC
            LIMIT 6
        ");

            $followsByContactMethod = [];
            foreach ($contactMethods as $item) {
                $followsByContactMethod[$item->name] = (int)$item->total;
            }

            // ✅ 3. نتائج الاتصال
            $contactResults = DB::select("
            SELECT cr.name, COUNT(*) as total
            FROM customer_follows cf
            INNER JOIN customers c ON cf.customer_id = c.id
            INNER JOIN contact_results cr ON cf.contact_result_id = cr.id
            WHERE c.deleted_at IS NULL 
            AND cf.contact_result_id IS NOT NULL
            GROUP BY cr.name
            ORDER BY total DESC
            LIMIT 6
        ");

            $followsByContactResult = [];
            foreach ($contactResults as $item) {
                $followsByContactResult[$item->name] = (int)$item->total;
            }

            // ✅ 4. المستخدمين
            $users = DB::select("
            SELECT u.name, COUNT(*) as total
            FROM customer_follows cf
            INNER JOIN customers c ON cf.customer_id = c.id
            INNER JOIN users u ON cf.created_by = u.id
            WHERE c.deleted_at IS NULL 
            AND cf.created_by IS NOT NULL
            GROUP BY u.name
            ORDER BY total DESC
            LIMIT 5
        ");

            $followsByUser = [];
            foreach ($users as $item) {
                $followsByUser[$item->name] = (int)$item->total;
            }

            // ✅ 5. التوزيع الشهري
            $months = DB::select("
            SELECT 
                DATE_FORMAT(cf.created_at, '%b %Y') as month,
                COUNT(*) as total
            FROM customer_follows cf
            INNER JOIN customers c ON cf.customer_id = c.id
            WHERE c.deleted_at IS NULL 
            AND cf.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY DATE_FORMAT(cf.created_at, '%Y-%m')
        ");

            $followsByMonth = [];
            foreach ($months as $item) {
                $followsByMonth[$item->month] = (int)$item->total;
            }

            return [
                'total_follows' => $stats->total_follows ?? 0,
                'upcoming_follows' => $stats->upcoming_follows ?? 0,
                'recent_follows' => $stats->recent_follows ?? 0,
                'follows_by_contact_method' => $followsByContactMethod,
                'follows_by_contact_result' => $followsByContactResult,
                'follows_by_user' => $followsByUser,
                'follows_by_month' => $followsByMonth,
            ];
        } catch (\Exception $e) {
            Log::error('Error in getReportStatistics: ' . $e->getMessage());

            return [
                'total_follows' => 0,
                'upcoming_follows' => 0,
                'recent_follows' => 0,
                'follows_by_contact_method' => [],
                'follows_by_contact_result' => [],
                'follows_by_user' => [],
                'follows_by_month' => [],
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على بيانات النموذج
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        return [
            'contact_methods' => SettingsContactMethod::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
            'customer_statuses' => SettingsCustomerStatus::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
            'contact_results' => ContactResult::where('status', true)->select('id', 'name')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'customers' => $this->getInitialCustomers(),
            'companies' => Company::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
            'portfolios' => Portfolio::where('status', 'active')->select('id', 'name', 'company_id')->orderBy('name')->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على عدد المتابعات حسب الفلاتر
    |--------------------------------------------------------------------------
    */
    public function getFollowsCount(array $filters): int
    {
        try {
            $query = $this->buildFilteredQuery($filters);
            return $query->count();
        } catch (\Exception $e) {
            Log::error('Error in getFollowsCount: ' . $e->getMessage());
            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المتابعات
    |--------------------------------------------------------------------------
    */
    public function exportFollowsReport(array $filters): BinaryFileResponse
    {
        $query = $this->buildFilteredQuery($filters)->with([
            'customer.company',
            'customer.portfolio',
            'contactMethod',
            'customerStatus',
            'contactResult',
            'createdBy',
            'fullPaymentDetails',
            'partialPaymentDetails',
            'promisePaymentDetails',
            'callbackDetails',
            'documentRequestDetails.documentReason',
            'objectionDetails',
            'recoveryDetails',
        ]);

        $totalCount = (clone $query)->count();
        $fileName   = 'customer_follows_report_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(
            new CustomerFollowReportExport($query, $filters, [], $totalCount),
            $fileName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المتابعات إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportFollowsReportToPDF(array $filters): BinaryFileResponse
    {
        try {
            $query = $this->buildFilteredQuery($filters);
            $follows = $query->orderBy('customer_follows.created_at')->get();
            $totalFollows = $follows->count();

            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;
            $dateRange = $this->getDateRangeText($dateFrom, $dateTo);

            $mpdf = new \Mpdf\Mpdf([
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
                    (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
                    [public_path('fonts/Almarai')]
                ),
                'fontdata' => array_merge(
                    (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'],
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

            $html = $this->generateFollowsReportHTML(
                $follows,
                $totalFollows,
                $dateRange,
                $appliedFilters
            );

            $mpdf->WriteHTML($html);

            $fileName = 'تقرير_متابعات_العملاء_' . now()->format('Y_m_d_H_i_s') . '.pdf';

            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir . '/' . $fileName;
            $mpdf->Output($tempPath, 'F');

            return response()->download($tempPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Log::error('exportFollowsReportToPDF error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                // الحقول الأساسية من جدول customer_follows
                ['key' => 'id', 'label' => 'رقم المتابعة', 'selected' => false],
                ['key' => 'customer_name', 'label' => 'اسم العميل', 'selected' => true],
                ['key' => 'customer_account_number', 'label' => 'رقم الحساب', 'selected' => true],
                ['key' => 'customer_identity_number', 'label' => 'رقم الهوية', 'selected' => true],
                ['key' => 'company_name', 'label' => 'الشركة', 'selected' => true],
                ['key' => 'portfolio_name', 'label' => 'المحفظة', 'selected' => true],
                ['key' => 'contact_method', 'label' => 'طريقة الاتصال', 'selected' => true],
                ['key' => 'contact_location', 'label' => 'موقع الاتصال', 'selected' => false],
                ['key' => 'contacted_person', 'label' => 'الشخص المتصل به', 'selected' => false],
                ['key' => 'contacted_person_name', 'label' => 'اسم الشخص المتصل به', 'selected' => false],
                ['key' => 'customer_status', 'label' => 'حالة العميل', 'selected' => true],
                ['key' => 'contact_result', 'label' => 'نتيجة الاتصال', 'selected' => true],
                ['key' => 'notes', 'label' => 'ملاحظات', 'selected' => false],
                ['key' => 'created_by', 'label' => 'أنشأ بواسطة', 'selected' => false],
                ['key' => 'created_at', 'label' => 'تاريخ الإنشاء', 'selected' => true],

                // الحقول الإضافية من العلاقات
                ['key' => 'payment_amount', 'label' => 'المبلغ المسدد (تم السداد)', 'selected' => false],
                ['key' => 'payment_date', 'label' => 'تاريخ السداد (تم السداد)', 'selected' => false],
                ['key' => 'payment_type', 'label' => 'نوع السداد (تم السداد)', 'selected' => false],
                ['key' => 'partial_payment_amount', 'label' => 'المبلغ المسدد (سداد جزئي)', 'selected' => false],
                ['key' => 'partial_payment_date', 'label' => 'تاريخ السداد (سداد جزئي)', 'selected' => false],
                ['key' => 'promise_amount', 'label' => 'المبلغ الموعود به', 'selected' => false],
                ['key' => 'promise_date', 'label' => 'تاريخ السداد الموعود', 'selected' => false],
                ['key' => 'callback_date', 'label' => 'تاريخ المعاودة', 'selected' => false],
                ['key' => 'callback_reason', 'label' => 'سبب المعاودة', 'selected' => false],
                ['key' => 'documents_date', 'label' => 'تاريخ طلب المستندات', 'selected' => false],
                ['key' => 'documents_reason', 'label' => 'سبب طلب المستندات', 'selected' => false],
                ['key' => 'objection_reason', 'label' => 'سبب الاعتراض', 'selected' => false],
                ['key' => 'recovery_reason', 'label' => 'سبب الاسترداد', 'selected' => false],
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المتابعات إلى Excel مع الحقول المخصصة
    |--------------------------------------------------------------------------
    */
    public function exportFilteredFollowsToExcel(array $filters, array $selectedFields = []): string
    {
        $query = $this->buildFilteredQuery($filters)->with([
            'customer.company',
            'customer.portfolio',
            'contactMethod',
            'customerStatus',
            'contactResult',
            'createdBy',
            'fullPaymentDetails',
            'partialPaymentDetails',
            'promisePaymentDetails',
            'callbackDetails',
            'documentRequestDetails.documentReason',
            'objectionDetails',
            'recoveryDetails',
        ]);

        $totalCount = (clone $query)->count();
        $fileName   = 'تقرير_متابعات_العملاء_مخصص_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        Excel::store(
            new CustomerFollowReportExport($query, $filters, $selectedFields, $totalCount),
            'exports/' . $fileName,
            'tenant_public'
        );

        return $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | بناء الاستعلام مع الفلاتر
    |--------------------------------------------------------------------------
    */
    private function buildFilteredQuery(array $filters)
    {
        $query = CustomerFollow::query()
            ->join('customers', 'customer_follows.customer_id', '=', 'customers.id')
            ->leftJoin('companies', 'customers.company_id', '=', 'companies.id')
            ->leftJoin('portfolios', 'customers.portfolio_id', '=', 'portfolios.id')
            ->leftJoin('settings_contact_methods', 'customer_follows.contact_method_id', '=', 'settings_contact_methods.id')
            ->leftJoin('settings_customer_status', 'customer_follows.customer_status_id', '=', 'settings_customer_status.id')
            ->leftJoin('contact_results', 'customer_follows.contact_result_id', '=', 'contact_results.id')
            ->leftJoin('users', 'customer_follows.created_by', '=', 'users.id')
            ->whereNull('customers.deleted_at')
            ->select(
                'customer_follows.*',
                'customers.name as customer_name',
                'companies.name as company_name',
                'portfolios.name as portfolio_name',
                'settings_contact_methods.name as contact_method_name',
                'settings_customer_status.name as customer_status_name',
                'contact_results.name as contact_result_name',
                'users.name as user_name'
            );

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
            if (empty($value)) continue;

            switch ($key) {
                case 'date_from':
                    $query->whereDate('customer_follows.created_at', '>=', $value);
                    break;
                case 'date_to':
                    $query->whereDate('customer_follows.created_at', '<=', $value);
                    break;
                case 'next_contact_from':
                    $query->whereDate('customer_follows.next_contact_datetime', '>=', $value);
                    break;
                case 'next_contact_to':
                    $query->whereDate('customer_follows.next_contact_datetime', '<=', $value);
                    break;
                case 'customer_id':
                    $query->where('customer_follows.customer_id', $value);
                    break;
                case 'contact_method_id':
                    $query->where('customer_follows.contact_method_id', $value);
                    break;
                case 'customer_status_id':
                    // تحديد الجدول بشكل صريح لتجنب الغموض
                    $query->where('customer_follows.customer_status_id', $value);
                    break;
                case 'contact_result_id':
                    $query->where('customer_follows.contact_result_id', $value);
                    break;
                case 'created_by':
                    $query->where('customer_follows.created_by', $value);
                    break;
                // إضافة فلاتر الشركة والمحفظة
                case 'company_id':
                    $query->where('customers.company_id', $value);
                    break;
                case 'portfolio_id':
                    $query->where('customers.portfolio_id', $value);
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

        if (!empty($filters['customer_id'])) {
            $customer = Customer::find($filters['customer_id']);
            if ($customer) {
                $appliedFilters[] = "العميل: {$customer->name}";
            }
        }

        if (!empty($filters['company_id'])) {
            $company = Company::find($filters['company_id']);
            if ($company) {
                $appliedFilters[] = "الشركة: {$company->name}";
            }
        }

        // إضافة فلتر المحفظة
        if (!empty($filters['portfolio_id'])) {
            $portfolio = Portfolio::find($filters['portfolio_id']);
            if ($portfolio) {
                $appliedFilters[] = "المحفظة: {$portfolio->name}";
            }
        }

        if (!empty($filters['contact_method_id'])) {
            $method = SettingsContactMethod::find($filters['contact_method_id']);
            if ($method) {
                $appliedFilters[] = "طريقة الاتصال: {$method->name}";
            }
        }

        if (!empty($filters['customer_status_id'])) {
            $status = SettingsCustomerStatus::find($filters['customer_status_id']);
            if ($status) {
                $appliedFilters[] = "حالة العميل: {$status->name}";
            }
        }

        if (!empty($filters['contact_result_id'])) {
            $result = ContactResult::find($filters['contact_result_id']);
            if ($result) {
                $appliedFilters[] = "نتيجة الاتصال: {$result->name}";
            }
        }

        if (!empty($filters['created_by'])) {
            $user = User::find($filters['created_by']);
            if ($user) {
                $appliedFilters[] = "أنشأ بواسطة: {$user->name}";
            }
        }

        if (!empty($filters['next_contact_from']) || !empty($filters['next_contact_to'])) {
            $nextFrom = $filters['next_contact_from'] ?? 'البدء';
            $nextTo = $filters['next_contact_to'] ?? 'النهاية';
            $appliedFilters[] = "الاتصال القادم: من {$nextFrom} إلى {$nextTo}";
        }

        if (isset($filters['has_upcoming'])) {
            $upcomingStatus = $filters['has_upcoming'] == '1' ? 'لديهم متابعات قادمة' : 'ليس لديهم متابعات قادمة';
            $appliedFilters[] = $upcomingStatus;
        }

        return empty($appliedFilters) ? 'لا توجد فلاتر مطبقة' : implode(' | ', $appliedFilters);
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء HTML للتقرير
    |--------------------------------------------------------------------------
    */
    private function generateFollowsReportHTML(
        $follows,
        int $totalFollows,
        string $dateRange,
        string $appliedFilters
    ): string {
        return view('tenants.reports.customer_follows.pdf', compact(
            'follows',
            'totalFollows',
            'dateRange',
            'appliedFilters'
        ))->render();
    }

    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    | البحث في العملاء بـ AJAX
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

        if (!empty($search)) {
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
                    'text' => $customer->account_number
                ];
            })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer ById
    |--------------------------------------------------------------------------
    | جلب عميل محدد
    */
    public function getCustomerById(int $customerId): ?array
    {
        $customer = Customer::select('id', 'name', 'account_number')
            ->find($customerId);

        if (!$customer) {
            return null;
        }

        if (
            !auth()->user()->can('customers.view') &&
            auth()->user()->can('customers.my_view') &&
            $customer->relationship_manager_id !== auth()->id()
        ) {
            return null;
        }

        return [
            'id' => $customer->id,
            'text' => $customer->account_number
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Initial Customers
    |--------------------------------------------------------------------------
    | جلب العملاء الأولية (10 عملاء)
   */
    public function getInitialCustomers(int $limit = 10): array
    {
        $query = Customer::select('id', 'name', 'account_number', 'phone');

        if (!auth()->user()->can('customers.view') && auth()->user()->can('customers.my_view')) {
            $query->where('relationship_manager_id', auth()->id());
        } elseif (!auth()->user()->can('customers.view')) {
            return [];
        }

        return $query->orderBy('account_number')
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->account_number
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
        $count = $this->getFollowsCount($filters);

        if ($count === 0) {
            return [
                'success' => false,
                'message' => 'لا توجد بيانات للتصدير',
                'count'   => 0,
            ];
        }

        $job        = new ExportCustomerFollowsJob($filters, $selectedFields, $userId);
        $totalParts = (int) ceil($count / ExportCustomerFollowsJob::MAX_ROWS_PER_EXPORT_FILE);

        dispatch($job->onQueue('exports'));

        return [
            'success'     => true,
            'export_id'   => $job->getExportId(),
            'count'       => $count,
            'total_parts' => $totalParts,
            'message'     => $count > self::LARGE_EXPORT_THRESHOLD
                ? "سيتم تصدير {$count} متابعة. سيتم إشعارك عند الانتهاء"
                : "جاري تصدير {$count} متابعة...",
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Async Export
    |--------------------------------------------------------------------------
    */
    public function cancelExport(string $exportId): void
    {
        Cache::put("export_cancel_{$exportId}", true, now()->addHours(2));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export Progress
    |--------------------------------------------------------------------------
    */
    public function getExportProgress(string $exportId): array
    {
        $progress = Cache::get("export_progress_{$exportId}");
        $result   = Cache::get("export_result_{$exportId}");

        if ($result) {
            if ($result['status'] === 'completed' && !empty($result['files'])) {
                $result['download_urls'] = array_map(
                    fn (string $file) => tenant_route_name('reports.customer_follows.download', ['file' => $file]),
                    $result['files']
                );

                // توافق مع الكود القديم الذي يتوقع file_name و file_size مفردة
                if (count($result['files']) === 1) {
                    $result['file_name'] = $result['files'][0];
                    $result['file_size'] = $result['file_sizes'][0] ?? null;
                }
            }

            return array_merge(['export_id' => $exportId], $result);
        }

        if ($progress) {
            // أضف روابط التحميل للملفات المكتملة أثناء المعالجة
            $completedFiles = $progress['completed_files'] ?? [];
            $downloadUrls   = array_map(
                fn (string $file) => tenant_route_name('reports.customer_follows.download', ['file' => $file]),
                $completedFiles
            );

            return array_merge(
                ['export_id' => $exportId, 'status' => 'processing'],
                $progress,
                ['download_urls' => $downloadUrls]
            );
        }

        return [
            'export_id' => $exportId,
            'status'    => 'not_found',
            'message'   => 'لم يتم العثور على عملية التصدير',
        ];
    }
}

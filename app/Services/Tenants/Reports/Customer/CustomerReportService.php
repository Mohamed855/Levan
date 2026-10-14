<?php

namespace App\Services\Tenants\Reports\Customer;

use App\Exports\Tenants\Reports\Customer\CustomersReportExport;
use App\Jobs\Tenants\Reports\Customer\ExportCustomersJob;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Reports\TenantExportAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerReportService
{
    private const LARGE_EXPORT_THRESHOLD = 5000;

    private const CHUNK_SIZE = 1000;

    /*
    |--------------------------------------------------------------------------
    | Get Report Statistics
    |--------------------------------------------------------------------------
    */
    public function getReportStatistics(): array
    {
        $stats = DB::selectOne("
        SELECT 
            COUNT(*) as total_customers,
            SUM(CASE WHEN customer_type = 'individual' THEN 1 ELSE 0 END) as individual_customers,
            SUM(CASE WHEN customer_type = 'company' THEN 1 ELSE 0 END) as company_customers,
            SUM(CASE WHEN last_contact_result_id IS NULL THEN 1 ELSE 0 END) as customers_without_contact,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_customers
         FROM customers
         WHERE deleted_at IS NULL
    ");

        $contactResults = [];
        $contactRaw = DB::select('
        SELECT cr.name, COUNT(*) as count
        FROM customers c
        INNER JOIN contact_results cr ON c.last_contact_result_id = cr.id
        WHERE c.deleted_at IS NULL
        GROUP BY cr.id, cr.name
        ORDER BY count DESC
        LIMIT 6
        ');
        foreach ($contactRaw as $row) {
            $contactResults[$row->name] = (int) $row->count;
        }
        $portfoliosData = [];
        $totalPortfoliosDebt = 0;
        $portfolios = DB::select("
        SELECT 
            p.name,
            SUM(CASE WHEN ct.type = 'debit' THEN ct.amount ELSE 0 END) - 
            SUM(CASE WHEN ct.type = 'credit' THEN ct.amount ELSE 0 END) as debt_amount
        FROM customer_transactions ct
        INNER JOIN customers c ON ct.customer_id = c.id AND c.deleted_at IS NULL
        INNER JOIN portfolios p ON c.portfolio_id = p.id
        GROUP BY p.id, p.name
        HAVING debt_amount > 0
        ORDER BY debt_amount DESC
        LIMIT 5
        ");

        foreach ($portfolios as $row) {
            $amount = (float) $row->debt_amount;
            $portfoliosData[$row->name] = $amount;
            $totalPortfoliosDebt += $amount;
        }
        $monthlyCustomers = [];
        $monthly = DB::select("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM customers
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          AND deleted_at IS NULL
        GROUP BY month
        ORDER BY month
       ");
        foreach ($monthly as $row) {
            $monthName = Carbon::createFromFormat('Y-m', $row->month)->format('M Y');
            $monthlyCustomers[$monthName] = (int) $row->count;
        }

        return [
            'total_customers' => (int) $stats->total_customers,
            'individual_customers' => (int) $stats->individual_customers,
            'company_customers' => (int) $stats->company_customers,
            'customers_with_debt' => 0,
            'customers_by_contact_result' => $contactResults,
            'customers_without_contact_result' => (int) $stats->customers_without_contact,
            'portfolios_by_amount' => $portfoliosData,
            'total_portfolios_debt' => $totalPortfoliosDebt,
            'customers_by_month' => $monthlyCustomers,
            'recent_customers' => (int) $stats->recent_customers,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        return [
            'companies' => Company::active()->select('id', 'name')->orderBy('name')->get(),
            'portfolios' => Portfolio::active()->select('id', 'name', 'company_id')->with('company:id,name')->orderBy('name')->get(),
            'countries' => SettingsCountry::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'customer_statuses' => SettingsCustomerStatus::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
            'contact_results' => ContactResult::where('status', true)->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customers Count
    |--------------------------------------------------------------------------
    */
    public function getCustomersCount(array $filters): int
    {
        try {
            if (empty($filters['debt_from']) && empty($filters['debt_to'])) {
                return $this->buildFilteredQuery($filters)->count();
            }

            $debtFrom = isset($filters['debt_from']) ? (float) $filters['debt_from'] : null;
            $debtTo = isset($filters['debt_to']) ? (float) $filters['debt_to'] : null;

            $query = Customer::query()
                ->leftJoin('customer_transactions as d', function ($join) {
                    $join->on('d.customer_id', '=', 'customers.id')->where('d.type', 'debit');
                })
                ->leftJoin('customer_transactions as c', function ($join) {
                    $join->on('c.customer_id', '=', 'customers.id')->where('c.type', 'credit');
                })
                ->selectRaw('customers.id, (COALESCE(SUM(d.amount),0) - COALESCE(SUM(c.amount),0)) as debt_amount')
                ->groupBy('customers.id');

            $this->applyFiltersToQuery($query, $filters);

            if (! is_null($debtFrom)) {
                $query->having('debt_amount', '>=', $debtFrom);
            }

            if (! is_null($debtTo)) {
                $query->having('debt_amount', '<=', $debtTo);
            }

            return DB::table(DB::raw("({$query->toSql()}) as sub"))
                ->mergeBindings($query->getQuery())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Export Customers Report - Enhanced Version
    |--------------------------------------------------------------------------
    */
    public function exportCustomersReport(array $filters): BinaryFileResponse
    {
        $query = $this->buildFilteredQuery($filters);
        $customers = $query->orderBy('customers.name')->get();

        $fileName = 'customers_report_'.now()->format('Y_m_d_H_i_s').'.xlsx';

        return Excel::download(new CustomersReportExport($customers, $filters), $fileName);
    }

    /*
    |--------------------------------------------------------------------------
    | Start Async Export
    |--------------------------------------------------------------------------
    */
    public function startAsyncExport(array $filters, array $selectedFields, int $userId): array
    {
        $count = $this->getCustomersCount($filters);

        if ($count === 0) {
            return [
                'success' => false,
                'message' => 'لا توجد بيانات للتصدير',
                'count' => 0,
            ];
        }

        $job = new ExportCustomersJob($filters, $selectedFields, $userId);
        app(TenantExportAccess::class)->registerExport($job->getExportId(), (int) $userId);

        dispatch($job->onQueue('exports'));

        return [
            'success' => true,
            'export_id' => $job->getExportId(),
            'count' => $count,
            'message' => $count > self::LARGE_EXPORT_THRESHOLD
                ? "سيتم تصدير {$count} عميل  سيتم إشعارك عند الانتهاء"
                : "جاري تصدير {$count} عميل...",
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

        $progress = Cache::get("export_progress_{$exportId}");
        $result = Cache::get("export_result_{$exportId}");

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

    /*
    |--------------------------------------------------------------------------
    | Export Customers Report To PDF
    |--------------------------------------------------------------------------
    */
    public function exportCustomersReportToPDF(array $filters): BinaryFileResponse
    {
        try {
            $query = $this->buildFilteredQuery($filters);
            $customers = $query->orderBy('customers.name')->get();
            $totalCustomers = $customers->count();
            $individualCustomers = $customers->where('customer_type', 'individual')->count();
            $companyCustomers = $customers->where('customer_type', 'company')->count();
            $totalDebt = $customers->sum('debt_amount');
            $customersWithDebt = $customers->where('debt_amount', '>', 0)->count();

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

            $html = $this->generateCustomersReportHTML(
                $customers,
                $totalCustomers,
                $individualCustomers,
                $companyCustomers,
                $totalDebt,
                $customersWithDebt,
                $dateRange,
                $appliedFilters
            );

            $mpdf->WriteHTML($html);

            $fileName = 'تقرير_العملاء_'.now()->format('Y_m_d_H_i_s').'.pdf';

            $tempDir = storage_path('app/public/temp');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir.'/'.$fileName;
            $mpdf->Output($tempPath, 'F');

            return response()->download($tempPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Export Fields
    |--------------------------------------------------------------------------
    */
    public function getAvailableExportFields(): array
    {
        return [
            'fields' => [
                ['key' => 'name', 'label' => 'اسم العميل الكامل', 'selected' => true],
                ['key' => 'first_name', 'label' => 'الاسم الأول', 'selected' => false],
                ['key' => 'father_name', 'label' => 'اسم الأب', 'selected' => false],
                ['key' => 'grandfather_name', 'label' => 'اسم الجد', 'selected' => false],
                ['key' => 'family_name', 'label' => 'اسم العائلة', 'selected' => false],
                ['key' => 'customer_type', 'label' => 'نوع العميل', 'selected' => true],
                ['key' => 'identity_number', 'label' => 'رقم الهوية', 'selected' => true],
                ['key' => 'phone', 'label' => 'رقم الهاتف', 'selected' => true],
                ['key' => 'primary_phone', 'label' => 'الرقم الافتراضي', 'selected' => true],
                ['key' => 'extra_phones', 'label' => 'الأرقام الإضافية', 'selected' => false],
                ['key' => 'account_number', 'label' => 'رقم الحساب', 'selected' => true],
                ['key' => 'commission_rate', 'label' => 'نسبة العمولة', 'selected' => false],
                ['key' => 'contract_date', 'label' => 'تاريخ العقد', 'selected' => false],
                ['key' => 'default_date', 'label' => 'تاريخ التعثر', 'selected' => false],
                ['key' => 'created_at', 'label' => 'تاريخ الإضافة', 'selected' => true],
                ['key' => 'updated_at', 'label' => 'تاريخ آخر تحديث', 'selected' => false],
                ['key' => 'company_name', 'label' => 'اسم الشركة', 'selected' => true],
                ['key' => 'portfolio_name', 'label' => 'المحفظة', 'selected' => true],
                ['key' => 'nationality', 'label' => 'الجنسية', 'selected' => false],
                ['key' => 'manager_name', 'label' => 'مسؤول العلاقة', 'selected' => true],
                ['key' => 'customer_status', 'label' => 'حالة العميل', 'selected' => true],
                ['key' => 'last_contact_result', 'label' => 'نتيجة آخر اتصال', 'selected' => false],
                ['key' => 'created_by', 'label' => 'أنشأ بواسطة', 'selected' => false],
                ['key' => 'debt_amount', 'label' => 'مبلغ المديونية', 'selected' => true],
                ['key' => 'total_credit', 'label' => 'المدفوع', 'selected' => true],
                ['key' => 'remaining_debt', 'label' => 'المتبقي', 'selected' => true],
                ['key' => 'days_elapsed', 'label' => 'أيام الاسناد', 'selected' => false],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Export Filtered Customers To Excel - Optimized Version
    |--------------------------------------------------------------------------
    */
    public function exportFilteredCustomersToExcel(array $filters, array $selectedFields = []): string
    {
        $count = $this->getCustomersCount($filters);

        if ($count > self::LARGE_EXPORT_THRESHOLD) {
            throw new \Exception('عدد السجلات كبير جداً. يرجى استخدام التصدير غير المتزامن.');
        }

        $query = $this->buildFilteredQuery($filters);

        $customers = $query->with([
            'phones',
            'primaryPhone',
            'notes',
            'attachments',
            'paymentPlans',
            'settlements',
            'smsMessages',
            'updateRequests',
            'createdBy',
            'lastContactResult',
        ])->orderBy('customers.name')->get();

        $enrichedCustomers = $customers->map(function ($customer) {
            // استخدام القيم التي تم جلبها بالفعل في buildFilteredQuery
            $debit = $customer->total_debit ?? 0;
            $credit = $customer->total_credit ?? 0;

            // المديونية الأصلية (إجمالي الديون)
            $customer->original_debt = $debit;

            // المبلغ المدفوع
            $customer->total_credit = $credit;

            // المتبقي من المديونية (المديونية الأصلية - المبلغ المدفوع)
            $customer->remaining_debt = $debit - $credit;

            $customer->days_elapsed = $customer->created_at ? now()->diffInDays($customer->created_at) : 0;

            $customer->transactions_count = $customer->transactions ? $customer->transactions->count() : 0;
            $customer->last_transaction_date = $customer->transactions ? $customer->transactions->sortByDesc('transaction_date')->first()?->transaction_date : null;
            $customer->notes_count = $customer->notes ? $customer->notes->count() : 0;
            $customer->attachments_count = $customer->attachments ? $customer->attachments->count() : 0;
            $customer->payment_plans_count = $customer->paymentPlans ? $customer->paymentPlans->count() : 0;
            $customer->settlements_count = $customer->settlements ? $customer->settlements->count() : 0;
            $customer->sms_count = $customer->smsMessages ? $customer->smsMessages->count() : 0;
            $customer->has_pending_requests = $customer->hasPendingUpdateRequests();
            $customer->all_phones = $customer->phones ? $customer->phones->pluck('phone')->implode(', ') : '';

            $primaryPhone = $customer->phones->firstWhere('is_primary', true)?->phone;

            $extraPhones = $customer->phones
                ->where('is_primary', false)
                ->pluck('phone')
                ->values();

            $customer->primary_phone = $primaryPhone;
            $customer->extra_phones = $extraPhones;

            return $customer;
        });

        $fileName = 'تقرير_العملاء_مخصص_'.now()->format('Y_m_d_H_i_s').'.xlsx';
        $relative = 'exports/'.$fileName;

        Excel::store(
            new CustomersReportExport($enrichedCustomers, $filters, $selectedFields),
            $relative,
            'tenant_public'
        );
        app(TenantExportAccess::class)->registerFile($fileName, (int) auth('tenant')->id());

        return $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | Build Filtered Query
    |--------------------------------------------------------------------------
    */
    private function buildFilteredQuery(array $filters)
    {
        $query = Customer::query()
            ->select('customers.*')
            ->selectSub(function ($query) {
                $query->from('customer_transactions')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('type', 'debit')
                    ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'total_debit')
            ->selectSub(function ($query) {
                $query->from('customer_transactions')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('type', 'credit')
                    ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'total_credit')
            ->selectRaw('(
            (SELECT COALESCE(SUM(amount), 0) 
             FROM customer_transactions 
             WHERE customer_id = customers.id AND type = "debit") - 
            (SELECT COALESCE(SUM(amount), 0) 
             FROM customer_transactions 
             WHERE customer_id = customers.id AND type = "credit")
        ) as debt_amount')
            ->with([
                'company:id,name',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'customerStatus:id,name',
                'lastContactResult:id,name',
                'createdBy:id,name',
            ]);

        $this->applyFiltersToQuery($query, $filters);

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters To Query
    |--------------------------------------------------------------------------
    */
    private function applyFiltersToQuery($query, array $filters, bool $isDebtSubquery = false): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($key) {
                case 'date_from':
                    $query->whereDate('customers.created_at', '>=', $value);
                    break;

                case 'date_to':
                    $query->whereDate('customers.created_at', '<=', $value);
                    break;

                case 'customer_status_id':
                    $query->where('customers.customer_status_id', $value);
                    break;

                case 'customer_type':
                    $query->where('customers.customer_type', $value);
                    break;

                case 'company_id':
                    $query->where('customers.company_id', $value);
                    break;

                case 'portfolio_id':
                    $query->where('customers.portfolio_id', $value);
                    break;

                case 'nationality':
                    $query->where('customers.nationality', $value);
                    break;

                case 'relationship_manager_id':
                    if (is_array($value)) {
                        $cleanValues = array_filter($value);
                        if (! empty($cleanValues)) {
                            $query->whereIn('customers.relationship_manager_id', $cleanValues);
                        }
                    } else {
                        $query->where('customers.relationship_manager_id', $value);
                    }
                    break;

                case 'contact_result_id':
                    if (is_array($value)) {
                        $cleanValues = array_filter($value);
                        if (! empty($cleanValues)) {
                            $query->whereIn('customers.last_contact_result_id', $cleanValues);
                        }
                    } else {
                        $query->where('customers.last_contact_result_id', $value);
                    }
                    break;

                case 'debt_from':
                    if ($isDebtSubquery) {
                        $query->where('sub.debt_amount', '>=', (float) $value);
                    } else {
                        $query->havingRaw('debt_amount >= ?', [(float) $value]);
                    }
                    break;

                case 'debt_to':
                    if ($isDebtSubquery) {
                        $query->where('sub.debt_amount', '<=', (float) $value);
                    } else {
                        $query->havingRaw('debt_amount <= ?', [(float) $value]);
                    }
                    break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Date Range Text
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
    | Get Applied Filters Text
    |--------------------------------------------------------------------------
    */
    private function getAppliedFiltersText(array $filters): string
    {
        $appliedFilters = [];

        if (! empty($filters['customer_type'])) {
            $type = $filters['customer_type'] === 'individual' ? 'الأفراد' : 'الشركات';
            $appliedFilters[] = "نوع العميل: {$type}";
        }

        if (! empty($filters['company_id'])) {
            $company = Company::find($filters['company_id']);
            if ($company) {
                $appliedFilters[] = "الشركة: {$company->name}";
            }
        }

        if (! empty($filters['customer_status_id'])) {
            $status = SettingsCustomerStatus::find($filters['customer_status_id']);
            if ($status) {
                $appliedFilters[] = "الحالة: {$status->name}";
            }
        }

        if (! empty($filters['portfolio_id'])) {
            $portfolio = Portfolio::find($filters['portfolio_id']);
            if ($portfolio) {
                $appliedFilters[] = "المحفظة: {$portfolio->name}";
            }
        }

        if (! empty($filters['nationality'])) {
            $country = SettingsCountry::find($filters['nationality']);
            if ($country) {
                $appliedFilters[] = "الجنسية: {$country->name}";
            }
        }

        if (! empty($filters['relationship_manager_id'])) {
            $manager = User::find($filters['relationship_manager_id']);
            if ($manager) {
                $appliedFilters[] = "مسؤول العلاقة: {$manager->name}";
            }
        }

        if (! empty($filters['contact_result_id'])) {
            $contactResult = ContactResult::find($filters['contact_result_id']);
            if ($contactResult) {
                $appliedFilters[] = "نتيجة الاتصال: {$contactResult->name}";
            }
        }

        if (! empty($filters['debt_from']) || ! empty($filters['debt_to'])) {
            $debtFrom = $filters['debt_from'] ?? '0';
            $debtTo = $filters['debt_to'] ?? '∞';
            $appliedFilters[] = "المديونية: من {$debtFrom} إلى {$debtTo}";
        }

        if (isset($filters['has_debt'])) {
            $debtStatus = $filters['has_debt'] == '1' ? 'العملاء المدينون فقط' : 'العملاء بدون مديونية';
            $appliedFilters[] = $debtStatus;
        }

        return empty($appliedFilters) ? 'لا توجد فلاتر مطبقة' : implode(' | ', $appliedFilters);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Customers Report HTML
    |--------------------------------------------------------------------------
    */
    private function generateCustomersReportHTML(
        $customers,
        int $totalCustomers,
        int $individualCustomers,
        int $companyCustomers,
        float $totalDebt,
        int $customersWithDebt,
        string $dateRange,
        string $appliedFilters
    ): string {
        return view('reports.customers.pdf', compact(
            'customers',
            'totalCustomers',
            'individualCustomers',
            'companyCustomers',
            'totalDebt',
            'customersWithDebt',
            'dateRange',
            'appliedFilters'
        ))->render();
    }
}

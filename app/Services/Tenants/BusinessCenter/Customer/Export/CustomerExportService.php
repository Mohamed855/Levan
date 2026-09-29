<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Export;

use App\Exports\Tenants\BusinessCenter\CustomersExport;
use App\Jobs\Tenants\BusinessCenter\Customer\ExportCustomersByIdsJob;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class CustomerExportService
{
    private const EXPORT_ABSOLUTE_MAX    = 25000; // أعلى عدد عملاء يُسمح بتصديرهم في الطلب الواحد.
    private const EXPORT_QUEUE_THRESHOLD = 3000;  // عند تجاوز هذا العدد يتم تنفيذ التصدير عبر job في الخلفية

    public function exportCustomers(array $customerIds, array $selectedFields = [], ?string $truncateBy = null): array
    {
        $customerIds    = array_values(array_unique(array_filter($customerIds)));
        $requestedCount = count($customerIds);

        $truncated = $requestedCount > self::EXPORT_ABSOLUTE_MAX;

        if ($truncated) {
            $truncateBy  = ($truncateBy !== null && array_key_exists($truncateBy, self::TRUNCATE_CRITERIA_LABELS)) ? $truncateBy : 'created_desc';
            $customerIds = $this->truncateCustomerIdsByCriteria($customerIds, $truncateBy, self::EXPORT_ABSOLUTE_MAX);
        }

        $count = count($customerIds);

        $truncationNotice = $truncated
            ? "تم تحديد {$requestedCount} عميل، والحد الأقصى المسموح به للتصدير دفعة واحدة هو " . self::EXPORT_ABSOLUTE_MAX . " عميل. سيتم تصدير " . self::EXPORT_ABSOLUTE_MAX . " عميل حسب معيار (" . self::TRUNCATE_CRITERIA_LABELS[$truncateBy] . ") فقط."
            : null;

        // اذا العدد كبير
        if ($count > self::EXPORT_QUEUE_THRESHOLD) {
            $job = new ExportCustomersByIdsJob($customerIds, $selectedFields, $truncateBy);
            dispatch($job->onQueue('exports'));

            return [
                'success'   => true,
                'is_async'  => true,
                'truncated' => $truncated,
                'export_id' => $job->getExportId(),
                'count'     => $count,
                'message'   => $truncationNotice ?? "سيتم تصدير {$count} عميل في الخلفية، سيتم إشعارك عند الانتهاء",
            ];
        }

        $customers  = $this->getCustomersForExport($customerIds, $truncateBy);
        $fileName   = 'customers_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        Excel::store(new CustomersExport($customers, $selectedFields), 'exports/' . $fileName, 'tenant_public');

        return [
            'success'   => true,
            'is_async'  => false,
            'truncated' => $truncated,
            'count'     => $count,
            'file_name' => $fileName,
            'message'   => $truncationNotice ?? 'تم تصدير البيانات بنجاح',
        ];
    }

    /**
     * Get progress/result of a background customer export job.
     */
    public function getExportProgress(string $exportId): array
    {
        $result = Cache::get("customers_export_result_{$exportId}");
        if ($result) {
            return array_merge(['export_id' => $exportId], $result);
        }

        $progress = Cache::get("customers_export_progress_{$exportId}");
        if ($progress) {
            return array_merge(['export_id' => $exportId, 'status' => 'processing'], $progress);
        }

        return [
            'export_id' => $exportId,
            'status'    => 'not_found',
            'message'   => 'لم يتم العثور على عملية التصدير'
        ];
    }

    /*
    |============================================================================
    |============================================================================
    |                          Private Methods
    |============================================================================
    |============================================================================
    */
    private const TRUNCATE_CRITERIA_LABELS = [
        'created_desc' => 'الأحدث إنشاءً',
        'created_asc'  => 'الأقدم إنشاءً',
        'debt_desc'    => 'الأعلى مديونية',
        'debt_asc'     => 'الأقل مديونية',
        'paid_desc'    => 'الأعلى دفعًا',
        'paid_asc'     => 'الأقل دفعًا',
    ];

    private function getCustomersForExport(array $customerIds, ?string $sortBy = null): Collection
    {
        $query = Customer::whereIn('id', $customerIds)
            ->with([
                'company:id,name',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'createdBy:id,name',
                'customerStatus:id,name',
                'phones'
            ])
            ->addSelect([
                '*',
                'total_debit'  => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')->whereColumn('customer_id', 'customers.id')->where('type', 'debit'),
                'total_credit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')->whereColumn('customer_id', 'customers.id')->where('type', 'credit'),
            ]);

        switch ($sortBy) {
            case 'created_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'debt_desc':
                $query->orderByRaw('(total_debit - total_credit) desc');
                break;
            case 'debt_asc':
                $query->orderByRaw('(total_debit - total_credit) asc');
                break;
            case 'paid_desc':
                $query->orderBy('total_credit', 'desc');
                break;
            case 'paid_asc':
                $query->orderBy('total_credit', 'asc');
                break;
            default:
                $query->orderBy('name');
                break;
        }

        return $query->get();
    }

    private function truncateCustomerIdsByCriteria(array $customerIds, string $truncateBy, int $max): array
    {
        // نمرر المعرفات على دفعات لتفادي خطأ 1390 (Too many placeholders)
        // الذي يحدث عند استخدام whereIn بقائمة معرفات كبيرة جدًا دفعة واحدة.
        $customers = collect();

        foreach (array_chunk($customerIds, 1000) as $chunkIds) {
            $chunk = Customer::whereIn('id', $chunkIds)
                ->addSelect([
                    '*',
                    'total_debit'  => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')->whereColumn('customer_id', 'customers.id')->where('type', 'debit'),
                    'total_credit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')->whereColumn('customer_id', 'customers.id')->where('type', 'credit'),
                ])
                ->get();

            $customers = $customers->merge($chunk);
        }

        $sorted = match ($truncateBy) {
            'created_asc' => $customers->sortBy('created_at'),
            'debt_desc'   => $customers->sortByDesc(fn($c) => ($c->total_debit ?? 0) - ($c->total_credit ?? 0)),
            'debt_asc'    => $customers->sortBy(fn($c) => ($c->total_debit ?? 0) - ($c->total_credit ?? 0)),
            'paid_desc'   => $customers->sortByDesc('total_credit'),
            'paid_asc'    => $customers->sortBy('total_credit'),
            default       => $customers->sortByDesc('created_at'), // created_desc
        };

        return $sorted->take($max)->pluck('id')->toArray();
    }
}

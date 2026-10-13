<?php

namespace App\Jobs\Tenants\Reports\Customer;

use App\Exports\Tenants\Reports\Customer\CustomersReportExport;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public $tries = 3;

    public $maxExceptions = 1;

    protected $filters;

    protected $selectedFields;

    protected $userId;

    protected $exportId;

    protected $fileName;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($filters, $selectedFields, $userId)
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->userId = $userId;
        $this->exportId = uniqid('export_', true);
        $this->fileName = 'customers_export_'.now()->format('Y_m_d_H_i_s').'_'.$this->exportId.'.xlsx';
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        try {
            $this->updateProgress(0, 'بدء عملية التصدير...');

            // ⭐ تحميل الهواتف هنا ⭐
            $query = $this->buildQuery()->with(['phones']);
            $totalCount = $query->count();

            if ($totalCount === 0) {
                $this->updateProgress(100, 'لا توجد بيانات للتصدير');

                return;
            }

            $this->updateProgress(10, "جاري تصدير {$totalCount} عميل...");

            $customers = collect();
            $chunkSize = 1000;
            $processedCount = 0;

            $query->chunk($chunkSize, function ($chunk) use (&$customers, &$processedCount, $totalCount) {

                foreach ($chunk as $customer) {

                    // الحسابات المالية
                    $debit = $customer->total_debit ?? 0;
                    $credit = $customer->total_credit ?? 0;

                    $customer->original_debt = $debit;
                    $customer->total_credit = $credit;
                    $customer->remaining_debt = $debit - $credit;
                    $customer->days_elapsed = $customer->created_at
                        ? now()->diffInDays($customer->created_at)
                        : 0;

                    // ⭐⭐ تجهيز أرقام الهواتف (الحل الأساسي) ⭐⭐
                    $primaryPhone = $customer->phones
                        ->firstWhere('is_primary', true)?->phone;

                    $extraPhones = $customer->phones
                        ->where('is_primary', false)
                        ->pluck('phone')
                        ->values();

                    $customer->primary_phone = $primaryPhone;
                    $customer->extra_phones = $extraPhones;
                }

                $customers = $customers->merge($chunk);
                $processedCount += $chunk->count();

                $progress = 10 + (($processedCount / $totalCount) * 70);
                $this->updateProgress($progress, "معالجة {$processedCount} من {$totalCount} عميل...");
            });

            $this->updateProgress(85, 'إنشاء ملف Excel...');

            $relative = 'exports/'.$this->fileName;
            Excel::store(
                new CustomersReportExport($customers, $this->filters, $this->selectedFields),
                $relative,
                'tenant_public'
            );

            $fileSize = Storage::disk('tenant_public')->size($relative);

            Cache::put("export_result_{$this->exportId}", [
                'status' => 'completed',
                'file_name' => $this->fileName,
                'relative_path' => $relative,
                'file_size' => $this->formatBytes($fileSize),
                'total_records' => $totalCount,
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(100, 'تم التصدير بنجاح');
        } catch (\Exception $e) {

            Cache::put("export_result_{$this->exportId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(-1, 'فشل في التصدير: '.$e->getMessage());
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Build Query
    |--------------------------------------------------------------------------
    */
    private function buildQuery()
    {
        $query = Customer::withSum([
            'transactions as total_debit' => function ($query) {
                $query->where('type', 'debit');
            },
        ], 'amount')
            ->withSum([
                'transactions as total_credit' => function ($query) {
                    $query->where('type', 'credit');
                },
            ], 'amount')
            ->with([
                'company:id,name',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'customerStatus:id,name',
                'lastContactResult:id,name',
                'createdBy:id,name',
            ]);

        foreach ($this->filters as $key => $value) {
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
                    $query->where('customer_status_id', $value);
                    break;
                case 'customer_type':
                    $query->where('customer_type', $value);
                    break;
                case 'company_id':
                    $query->where('company_id', $value);
                    break;
                case 'portfolio_id':
                    $query->where('portfolio_id', $value);
                    break;
                case 'nationality':
                    $query->where('nationality', $value);
                    break;
                case 'relationship_manager_id':
                    if (is_array($value)) {
                        $cleanValues = array_filter($value, function ($v) {
                            return ! empty($v);
                        });
                        if (! empty($cleanValues)) {
                            $query->whereIn('relationship_manager_id', $cleanValues);
                        }
                    } elseif (! empty($value)) {
                        $query->where('relationship_manager_id', $value);
                    }
                    break;
                case 'contact_result_id':
                    if (is_array($value)) {
                        $cleanValues = array_filter($value, function ($v) {
                            return ! empty($v);
                        });
                        if (! empty($cleanValues)) {
                            $query->whereIn('last_contact_result_id', $cleanValues);
                        }
                    } elseif (! empty($value)) {
                        $query->where('last_contact_result_id', $value);
                    }
                    break;
                case 'debt_from':
                    $query->havingRaw('(total_debit - total_credit) >= ?', [(float) $value]);
                    break;
                case 'debt_to':
                    $query->havingRaw('(total_debit - total_credit) <= ?', [(float) $value]);
                    break;
            }
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Progress
    |--------------------------------------------------------------------------
    */
    private function updateProgress($percentage, $message)
    {
        Cache::put("export_progress_{$this->exportId}", [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }

    /*
    |--------------------------------------------------------------------------
    | Format Bytes
    |--------------------------------------------------------------------------
    */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Job Handler
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception)
    {
        Cache::put("export_result_{$this->exportId}", [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'created_at' => now()->toDateTimeString(),
        ], now()->addHours(24));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export ID
    |--------------------------------------------------------------------------
    */
    public function getExportId()
    {
        return $this->exportId;
    }
}

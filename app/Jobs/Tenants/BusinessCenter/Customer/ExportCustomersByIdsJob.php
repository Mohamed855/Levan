<?php

namespace App\Jobs\Tenants\BusinessCenter\Customer;

use App\Exports\Tenants\BusinessCenter\CustomersExport;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportCustomersByIdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout         = 1800;
    public $tries           = 3;
    public $maxExceptions   = 1;

    protected array $customerIds;
    protected array $selectedFields;
    protected ?string $sortBy;
    protected string $exportId;
    protected string $fileName;

    public function __construct(array $customerIds,  array $selectedFields = [], ?string $sortBy = null)
    {
        $this->customerIds      = $customerIds;
        $this->selectedFields   = $selectedFields;
        $this->sortBy           = $sortBy;
        $this->exportId         = uniqid('customers_export_', true);
        $this->fileName         = 'customers_export_' . now()->format('Y_m_d_H_i_s') . '_' . $this->exportId . '.xlsx';
    }


    public function handle(): void
    {
        try {
            $this->updateProgress(0, 'بدء عملية التصدير...');

            $totalCount     = count($this->customerIds);
            $customers      = collect();
            $processedCount = 0;

            foreach (array_chunk($this->customerIds, 1000) as $chunkIds) {
                $chunk = Customer::whereIn('id', $chunkIds)
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
                        'total_debit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')->whereColumn('customer_id', 'customers.id')->where('type', 'debit'),
                        'total_credit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')->whereColumn('customer_id', 'customers.id')->where('type', 'credit'),
                    ])->get();

                $customers       = $customers->merge($chunk);
                $processedCount += count($chunkIds);

                $progress = 5 + (($processedCount / $totalCount) * 75);
                $this->updateProgress($progress, "معالجة {$processedCount} من {$totalCount} عميل...");
            }

            $customers = $this->sortCustomers($customers);

            $this->updateProgress(85, 'إنشاء ملف Excel...');

            $relative = 'exports/' . $this->fileName;
            Excel::store(new CustomersExport($customers, $this->selectedFields), $relative, 'tenant_public');

            $fileSize = Storage::disk('tenant_public')->size($relative);

            Cache::put("customers_export_result_{$this->exportId}", [
                'status' => 'completed',
                'file_name' => $this->fileName,
                'file_size' => $this->formatBytes($fileSize),
                'total_records' => $customers->count(),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(100, 'تم التصدير بنجاح');
        } catch (\Throwable $e) {
            Cache::put("customers_export_result_{$this->exportId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(-1, 'فشل في التصدير: ' . $e->getMessage());
            throw $e;
        }
    }


    /*
    |============================================================================
    |============================================================================
    |                           Private Methods
    |============================================================================
    |============================================================================
    */
    private function __sortCustomers(\Illuminate\Support\Collection $customers): \Illuminate\Support\Collection
    {
        $remainingDebt = fn($customer) => (float) ($customer->total_debit ?? 0) - (float) ($customer->total_credit ?? 0);

        switch ($this->sortBy) {
            case 'created_asc':
                return $customers->sortBy('id')->values();
            case 'created_desc':
                return $customers->sortByDesc('id')->values();
            case 'debt_desc':
                return $customers->sortByDesc($remainingDebt)->values();
            case 'debt_asc':
                return $customers->sortBy($remainingDebt)->values();
            case 'paid_desc':
                return $customers->sortByDesc(fn($c) => (float) ($c->total_credit ?? 0))->values();
            case 'paid_asc':
                return $customers->sortBy(fn($c) => (float) ($c->total_credit ?? 0))->values();
            default:
                return $customers->sortBy('name')->values();
        }
    }


    private function sortCustomers($customers)
    {
        $sorted = match ($this->sortBy) {
            'created_asc'  => $customers->sortBy(['created_at', 'id']),
            'created_desc' => $customers->sortByDesc(['created_at', 'id']),
            'debt_desc'    => $customers->sortByDesc(fn($c) => ($c->total_debit ?? 0) - ($c->total_credit ?? 0)),
            'debt_asc'     => $customers->sortBy(fn($c) => ($c->total_debit ?? 0) - ($c->total_credit ?? 0)),
            'paid_desc'    => $customers->sortByDesc('total_credit'),
            'paid_asc'     => $customers->sortBy('total_credit'),
            default        => $customers->sortBy('name'),
        };

        return $sorted->values();
    }

    private function updateProgress(float $percentage, string $message): void
    {
        Cache::put("customers_export_progress_{$this->exportId}", [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }

    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put("customers_export_result_{$this->exportId}", [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'created_at' => now()->toDateTimeString(),
        ], now()->addHours(24));
    }

    public function getExportId(): string
    {
        return $this->exportId;
    }
}

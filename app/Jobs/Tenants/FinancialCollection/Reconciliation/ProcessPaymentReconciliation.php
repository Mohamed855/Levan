<?php

namespace App\Jobs\Tenants\FinancialCollection\Reconciliation;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliation;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessPaymentReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */
    protected array $data;
    protected string $jobId;
    protected ?PaymentReconciliation $reconciliation = null;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->jobId = $data['job_id'];
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Job
    |--------------------------------------------------------------------------
    */
    public function handle(): void
    {
        try {
            $this->updateProgress('processing', 0, 0);

            $fileData = $this->readExcelFile();

            $this->reconciliation = $this->createReconciliation($fileData['total_rows']);

            $this->processRows($fileData['rows'], $fileData['total_rows']);

            $this->calculateSystemOnlyCollections();

            $this->reconciliation->update([
                'status' => 'completed',
                'processed_at' => now()
            ]);

            $this->updateProgress('completed', $fileData['total_rows'], $fileData['total_rows']);

            Storage::delete($this->data['temp_file_path']);

        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Read Excel File
    |--------------------------------------------------------------------------
    */
    protected function readExcelFile(): array
    {
        $filePath = storage_path('app/' . $this->data['temp_file_path']);
        
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $highestRow = $sheet->getHighestRow();
        $mappings = $this->data['field_mappings'];
        
        // قراءة Headers من الصف الأول
        $headers = [];
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $headerValue = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            $headers[$col] = trim((string)$headerValue);
        }
        
        // البحث عن أرقام الأعمدة
        $accountCol = $this->findColumnIndex($headers, $mappings['account_number']['column']);
        $amountCol = $this->findColumnIndex($headers, $mappings['collected_amount']['column']);
        $dateCol = $this->findColumnIndex($headers, $mappings['collection_date']['column']);
        
        if (!$accountCol || !$amountCol || !$dateCol) {
            throw new \Exception('لم يتم العثور على الأعمدة المطلوبة في الملف');
        }
        
        // قراءة البيانات
        $rows = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $accountNumber = trim((string)$sheet->getCellByColumnAndRow($accountCol, $row)->getValue());
            
            if (empty($accountNumber)) {
                continue;
            }
            
            $amount = $sheet->getCellByColumnAndRow($amountCol, $row)->getValue();
            $dateValue = $sheet->getCellByColumnAndRow($dateCol, $row)->getValue();
            $date = $this->parseDate($dateValue);
            
            $rows[] = [
                'row_number' => $row,
                'account_number' => $accountNumber,
                'amount' => (float)$amount,
                'date' => $date,
            ];
        }
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet, $reader);
        gc_collect_cycles();
        
        return [
            'total_rows' => count($rows),
            'rows' => $rows
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Find Column Index by Header Name
    |--------------------------------------------------------------------------
    */
    protected function findColumnIndex(array $headers, string $searchHeader): ?int
    {
        $searchHeader = trim($searchHeader);
        
        foreach ($headers as $colIndex => $headerName) {
            if (trim($headerName) === $searchHeader) {
                return $colIndex;
            }
        }
        
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Parse Date
    |--------------------------------------------------------------------------
    */
    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Reconciliation Record
    |--------------------------------------------------------------------------
    */
    protected function createReconciliation(int $totalRows): PaymentReconciliation
    {
        return PaymentReconciliation::create([
            'company_id' => $this->data['company_id'],
            'portfolio_id' => $this->data['portfolio_id'],
            'reconciliation_month' => $this->data['reconciliation_month'],
            'file_path' => $this->data['temp_file_path'],
            'total_rows' => $totalRows,
            'status' => 'processing',
            'created_by' => $this->data['user_id'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Process Rows
    |--------------------------------------------------------------------------
    */
    protected function processRows(array $rows, int $totalRows): void
    {
        $processed = 0;
        
        foreach ($rows as $rowData) {
            $this->processRow($rowData);
            $processed++;
            
            if ($processed % 10 === 0) {
                $this->updateProgress('processing', $processed, $totalRows);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Single Row
    |--------------------------------------------------------------------------
    */
    protected function processRow(array $rowData): void
    {
        DB::beginTransaction();
        
        try {
            $customer = Customer::where('account_number', $rowData['account_number'])
                ->where('portfolio_id', $this->data['portfolio_id'])
                ->first();

            if (!$customer) {
                $this->createErrorItem($rowData, 'العميل غير موجود في النظام');
                DB::commit();
                return;
            }

            if (!$rowData['date']) {
                $this->createErrorItem($rowData, 'تاريخ غير صحيح', $customer->id);
                DB::commit();
                return;
            }

            $matchResult = $this->findMatchingCollection($customer->id, $rowData['amount'], $rowData['date']);

            if ($matchResult['status'] === 'matched') {
                $this->createMatchedItem($rowData, $customer, $matchResult['collection']);
            } elseif ($matchResult['status'] === 'discrepancy') {
                $this->createDiscrepancyItem($rowData, $customer, $matchResult['collection']);
            } else {
                $newCollection = $this->createNewCollection($rowData, $customer);
                $this->createUnmatchedItem($rowData, $customer, $newCollection);
            }

            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->createErrorItem($rowData, 'خطأ في المعالجة: ' . $e->getMessage(), $customer->id ?? null);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Find Matching Collection
    |--------------------------------------------------------------------------
    */
    protected function findMatchingCollection(int $customerId, float $amount, Carbon $date): array
    {
        $exactMatch = Collection::where('customer_id', $customerId)
            ->where('status', 'approved')
            ->whereBetween('collected_amount', [$amount - 0.01, $amount + 0.01])
            ->whereBetween('collection_date', [$date->copy()->subDays(2), $date->copy()->addDays(2)])
            ->whereNull('reconciliation_item_id')
            ->first();

        if ($exactMatch) {
            return ['status' => 'matched', 'collection' => $exactMatch];
        }

        $partialMatch = Collection::where('customer_id', $customerId)
            ->where('status', 'approved')
            ->where(function ($query) use ($amount, $date) {
                $query->whereBetween('collected_amount', [$amount - 100, $amount + 100])
                      ->orWhereBetween('collection_date', [$date->copy()->subDays(7), $date->copy()->addDays(7)]);
            })
            ->whereNull('reconciliation_item_id')
            ->first();

        if ($partialMatch) {
            return ['status' => 'discrepancy', 'collection' => $partialMatch];
        }

        return ['status' => 'unmatched', 'collection' => null];
    }

    /*
    |--------------------------------------------------------------------------
    | Create Matched Item
    |--------------------------------------------------------------------------
    */
    protected function createMatchedItem(array $rowData, Customer $customer, Collection $collection): void
    {
        $item = PaymentReconciliationItem::create([
            'reconciliation_id' => $this->reconciliation->id,
            'account_number' => $rowData['account_number'],
            'file_amount' => $rowData['amount'],
            'file_date' => $rowData['date'],
            'row_number' => $rowData['row_number'],
            'customer_id' => $customer->id,
            'collection_id' => $collection->id,
            'match_status' => 'matched',
            'system_amount' => $collection->collected_amount,
            'system_date' => $collection->collection_date,
            'amount_difference' => $rowData['amount'] - $collection->collected_amount,
            'date_difference_days' => $rowData['date']->diffInDays($collection->collection_date),
        ]);

        $collection->update([
            'is_verified' => true,
            'reconciliation_item_id' => $item->id,
        ]);

        $this->reconciliation->increment('matched_count');
        $this->reconciliation->increment('matched_amount', $rowData['amount']);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Discrepancy Item
    |--------------------------------------------------------------------------
    */
    protected function createDiscrepancyItem(array $rowData, Customer $customer, Collection $collection): void
    {
        $item = PaymentReconciliationItem::create([
            'reconciliation_id' => $this->reconciliation->id,
            'account_number' => $rowData['account_number'],
            'file_amount' => $rowData['amount'],
            'file_date' => $rowData['date'],
            'row_number' => $rowData['row_number'],
            'customer_id' => $customer->id,
            'collection_id' => $collection->id,
            'match_status' => 'discrepancy',
            'system_amount' => $collection->collected_amount,
            'system_date' => $collection->collection_date,
            'amount_difference' => $rowData['amount'] - $collection->collected_amount,
            'date_difference_days' => $rowData['date']->diffInDays($collection->collection_date),
            'notes' => 'اختلاف في ' . 
                      (abs($rowData['amount'] - $collection->collected_amount) > 0.01 ? 'المبلغ' : '') .
                      (abs($rowData['date']->diffInDays($collection->collection_date)) > 2 ? ' والتاريخ' : ''),
        ]);

        $collection->update([
            'reconciliation_item_id' => $item->id,
        ]);

        $this->reconciliation->increment('discrepancy_count');
        $this->reconciliation->increment('discrepancy_amount', $rowData['amount']);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Unmatched Item
    |--------------------------------------------------------------------------
    */
    protected function createUnmatchedItem(array $rowData, Customer $customer, Collection $newCollection): void
    {
        $item = PaymentReconciliationItem::create([
            'reconciliation_id' => $this->reconciliation->id,
            'account_number' => $rowData['account_number'],
            'file_amount' => $rowData['amount'],
            'file_date' => $rowData['date'],
            'row_number' => $rowData['row_number'],
            'customer_id' => $customer->id,
            'collection_id' => $newCollection->id,
            'match_status' => 'unmatched',
            'notes' => 'تم إنشاء سند جديد من ملف المطابقة',
        ]);

        $newCollection->update([
            'reconciliation_item_id' => $item->id,
        ]);

        $this->reconciliation->increment('unmatched_count');
        $this->reconciliation->increment('unmatched_amount', $rowData['amount']);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Error Item
    |--------------------------------------------------------------------------
    */
    protected function createErrorItem(array $rowData, string $errorMessage, ?int $customerId = null): void
    {
        PaymentReconciliationItem::create([
            'reconciliation_id' => $this->reconciliation->id,
            'account_number' => $rowData['account_number'],
            'file_amount' => $rowData['amount'],
            'file_date' => $rowData['date'],
            'row_number' => $rowData['row_number'],
            'customer_id' => $customerId,
            'match_status' => 'error',
            'error_message' => $errorMessage,
        ]);

        $this->reconciliation->increment('error_count');
    }

    /*
    |--------------------------------------------------------------------------
    | Create New Collection
    |--------------------------------------------------------------------------
    */
    protected function createNewCollection(array $rowData, Customer $customer): Collection
    {
        return Collection::create([
            'customer_id' => $customer->id,
            'collected_amount' => $rowData['amount'],
            'collection_date' => $rowData['date'],
            'collection_type' => 'cash',
            'payment_type' => 'without_settlement',
            'payment_method_id' => 1,
            'receipt_number' => $this->generateReceiptNumber(),
            'status' => 'pending',
            'source' => 'reconciliation_file',
            'added_by' => $customer->relationship_manager_id ?? $this->data['user_id'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Receipt Number
    |--------------------------------------------------------------------------
    */
    protected function generateReceiptNumber(): string
    {
        $prefix = 'REC';
        $date = now()->format('Ymd');
        $lastCollection = Collection::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCollection ? 
            (int)substr($lastCollection->receipt_number, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate System Only Collections
    |--------------------------------------------------------------------------
    */
    protected function calculateSystemOnlyCollections(): void
    {
        $systemOnlyCount = Collection::whereHas('customer', function($q) {
                $q->where('portfolio_id', $this->data['portfolio_id']);
            })
            ->whereYear('collection_date', Carbon::parse($this->data['reconciliation_month'])->year)
            ->whereMonth('collection_date', Carbon::parse($this->data['reconciliation_month'])->month)
            ->where('is_verified', false)
            ->where('status', 'approved')
            ->count();

        $this->reconciliation->update([
            'system_only_count' => $systemOnlyCount
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Progress
    |--------------------------------------------------------------------------
    */
    protected function updateProgress(string $status, int $processed, int $total): void
    {
        $progressData = [
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'matched' => $this->reconciliation?->matched_count ?? 0,
            'unmatched' => $this->reconciliation?->unmatched_count ?? 0,
            'discrepancies' => $this->reconciliation?->discrepancy_count ?? 0,
            'errors' => $this->reconciliation?->error_count ?? 0,
        ];

        if ($status === 'completed' && $this->reconciliation) {
            $progressData['reconciliation_id'] = $this->reconciliation->id;
        }

        Cache::put("reconciliation_progress_{$this->jobId}", $progressData, 3600);
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Error
    |--------------------------------------------------------------------------
    */
    protected function handleError(\Exception $e): void
    {
        if ($this->reconciliation) {
            $this->reconciliation->update([
                'status' => 'failed',
                'processed_at' => now()
            ]);
        }

        Cache::put("reconciliation_progress_{$this->jobId}", [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'processed' => 0,
            'total' => 0,
        ], 3600);

        throw $e;
    }
}
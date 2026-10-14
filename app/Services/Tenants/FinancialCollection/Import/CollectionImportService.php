<?php

namespace App\Services\Tenants\FinancialCollection\Import;

use App\Jobs\Tenants\FinancialCollection\Collection\ProcessCollectionImport;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CollectionImportService
{
    protected CollectionImportValidationService $validationService;

    protected CollectionImportReportService $reportService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(
        CollectionImportValidationService $validationService,
        CollectionImportReportService $reportService
    ) {
        $this->validationService = $validationService;
        $this->reportService = $reportService;
    }

    public function cancelImport(string $tempPath): void
    {
        Storage::disk('tenant_public')->delete($tempPath);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Import Template
    |--------------------------------------------------------------------------
    */
    public function generateImportTemplate(): array
    {
        try {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setRightToLeft(true);

            $sheet->setCellValue('A1', 'نموذج استيراد التحصيلات المالية');
            $sheet->mergeCells('A1:C1')->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'رقم حساب العميل',
                'المبلغ المحصل',
                'تاريخ التحصيل',
            ];

            $this->setupTemplateColumns($sheet, $headers);
            $this->addSampleData($sheet);
            $this->formatTemplate($sheet, count($headers));

            $fileName = 'نموذج_استيراد_التحصيلات_'.now()->format('Y-m-d').'.xlsx';
            $tempDir = storage_path('app/public/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $filePath = $tempDir.'/'.$fileName;

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return ['success' => true, 'file_path' => $filePath, 'file_name' => $fileName];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'فشل في إنشاء النموذج: '.$e->getMessage()];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Upload And Analyze File
    |--------------------------------------------------------------------------
    */
    public function uploadAndAnalyzeFile(UploadedFile $file): array
    {
        $maxRowsForDirectProcessing = 1000;
        $absoluteMaxRows = 50000;

        try {
            $tempPath = $file->store('temp/imports', 'tenant_public');
            $allRows = Excel::toArray(null, Storage::disk('tenant_public')->path($tempPath))[0];

            $extracted = $this->findAndExtractData($allRows);

            if ($extracted === null || ! isset($extracted['headers'])) {
                Storage::disk('tenant_public')->delete($tempPath);

                return ['success' => false, 'message' => 'لم يتم العثور على صف العناوين في الملف.'];
            }

            $totalRows = $extracted['total_rows'];

            if ($totalRows > $absoluteMaxRows) {
                Storage::disk('tenant_public')->delete($tempPath);

                return [
                    'success' => false,
                    'message' => "الملف يحتوي على {$totalRows} صف. الحد الأقصى المسموح هو {$absoluteMaxRows} صف.",
                ];
            }

            $processingMethod = $totalRows > $maxRowsForDirectProcessing ? 'queue' : 'direct';

            return [
                'success' => true,
                'data' => [
                    'temp_path' => $tempPath,
                    'columns' => $this->getAvailableColumns($extracted['headers']),
                    'total_rows' => $totalRows,
                    'processing_method' => $processingMethod,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'فشل في تحليل الملف: '.$e->getMessage()];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Import File
    |--------------------------------------------------------------------------
    */
    public function processImportFile(array $importConfig): array
    {
        $fullPath = Storage::disk('tenant_public')->path($importConfig['temp_path']);

        if (! file_exists($fullPath)) {
            return ['success' => false, 'message' => 'الملف المؤقت غير موجود.'];
        }

        try {
            $allRows = Excel::toArray(null, $fullPath)[0];

            $extracted = $this->findAndExtractData($allRows);
            if ($extracted === null) {
                return ['success' => false, 'message' => 'ملف غير صالح، لم يتم العثور على العناوين.'];
            }

            if (! isset($extracted['headers'])) {
                return ['success' => false, 'message' => 'رؤوس الأعمدة غير موجودة.'];
            }

            if (! isset($extracted['data'])) {
                return ['success' => false, 'message' => 'بيانات الملف غير موجودة.'];
            }

            $dataRows = $extracted['data'];
            $totalRows = count($dataRows);

            if ($totalRows > 1000) {
                return $this->processLargeFileWithQueue($importConfig, $dataRows);
            }

            return $this->processSmallFileDirectly($importConfig, $dataRows);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'حدث خطأ أثناء معالجة الملف: '.$e->getMessage()];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get System Options
    |--------------------------------------------------------------------------
    */
    public function getSystemOptions(): array
    {
        return [
            'payment_methods' => SettingsPaymentMethod::where('status', 'active')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'users' => User::where('status', 'active')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'collection_types' => [
                ['value' => 'cash', 'label' => 'نقدي'],
                ['value' => 'scheduled', 'label' => 'مجدول'],
            ],
            'payment_types' => [
                ['value' => 'settlement', 'label' => 'سداد بتسوية'],
                ['value' => 'without_settlement', 'label' => 'سداد بدون تسوية'],
            ],
            'date_formats' => [
                ['value' => 'Y-m-d', 'label' => 'YYYY-MM-DD (2024-01-15)'],
                ['value' => 'd/m/Y', 'label' => 'DD/MM/YYYY (15/01/2024)'],
                ['value' => 'm/d/Y', 'label' => 'MM/DD/YYYY (01/15/2024)'],
                ['value' => 'd-m-Y', 'label' => 'DD-MM-YYYY (15-01-2024)'],
                ['value' => 'Y/m/d', 'label' => 'YYYY/MM/DD (2024/01/15)'],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Process Small File Directly
    |--------------------------------------------------------------------------
    */
    protected function processSmallFileDirectly(array $importConfig, array $dataRows): array
    {
        $results = [
            'total_rows' => count($dataRows),
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'new_collections' => [],
        ];

        DB::beginTransaction();
        try {
            $this->validationService->preloadCache($dataRows, $importConfig);

            foreach ($dataRows as $rowIndex => $row) {
                $validation = $this->validationService->validateRowData($row, $importConfig, $rowIndex + 2);

                if (! $validation['success']) {
                    $results['failed']++;
                    $results['errors'][] = ['row' => $rowIndex + 2, 'errors' => $validation['errors']];

                    continue;
                }

                $collection = $this->createCollectionFromRow($validation['data'], $importConfig);
                $results['successful']++;
                $results['new_collections'][] = $collection->id;
            }

            if ($results['failed'] > 0) {
                $reportData = ['errors' => $results['errors'], 'original_data' => $dataRows];
                $errorReportPath = $this->reportService->generateImportErrorReport($reportData);
                $results['error_report_path'] = $errorReportPath;
            }

            DB::commit();
            Storage::disk('tenant_public')->delete($importConfig['temp_path']);

            return ['success' => true, 'data' => $results];
        } catch (\Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => 'حدث خطأ أثناء الاستيراد: '.$e->getMessage()];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Large File With Queue
    |--------------------------------------------------------------------------
    */
    protected function processLargeFileWithQueue(array $importConfig, array $dataRows): array
    {
        $jobId = uniqid('import_', true);

        Cache::put("import_progress_{$jobId}", [
            'requested_by' => $importConfig['current_user_id'],
            'progress' => 0,
            'message' => 'تمت جدولة عملية الاستيراد.',
            'completed' => false,
            'failed' => false,
            'job_id' => $jobId,
            'updated_at' => now()->toISOString(),
        ], now()->addHours(24));

        dispatch(new ProcessCollectionImport($jobId, $importConfig, $dataRows));

        return [
            'success' => true,
            'data' => [
                'job_id' => $jobId,
                'total_rows' => count($dataRows),
                'is_queued' => true,
                'message' => 'تم إضافة عملية الاستيراد إلى قائمة الانتظار. ستتلقى إشعاراً عند اكتمالها.',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Columns
    |--------------------------------------------------------------------------
    */
    protected function getAvailableColumns(array $headers): array
    {
        $columns = [];
        foreach ($headers as $index => $header) {
            if (! empty(trim($header))) {
                $columns[] = [
                    'index' => $index,
                    'name' => trim($header),
                ];
            }
        }

        return $columns;
    }

    /*
    |--------------------------------------------------------------------------
    | Setup Template Columns
    |--------------------------------------------------------------------------
    */
    protected function setupTemplateColumns($sheet, $headers): void
    {
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column.'5', $header);
            $sheet->getStyle($column.'5')->getFont()->setBold(true);
            $sheet->getStyle($column.'5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
            $sheet->getStyle($column.'5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getColumnDimension($column)->setWidth(30);
            $column++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Add Sample Data
    |--------------------------------------------------------------------------
    */
    protected function addSampleData($sheet): void
    {
        $sampleData = [
            '123456',
            '1500.50',
            now()->format('Y-m-d'),
        ];

        $column = 'A';
        foreach ($sampleData as $data) {
            $sheet->setCellValue($column.'6', $data);
            $column++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Format Template
    |--------------------------------------------------------------------------
    */
    protected function formatTemplate($sheet, int $columnCount): void
    {
        $lastColumn = chr(ord('A') + $columnCount - 1);
        $sheet->getStyle("A5:{$lastColumn}6")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->freezePane('A6');
    }

    /*
    |--------------------------------------------------------------------------
    | Create Collection From Row
    |--------------------------------------------------------------------------
    */
    protected function createCollectionFromRow(array $validatedData, array $importConfig): Collection
    {
        return Collection::create([
            'customer_id' => $validatedData['customer_id'],
            'collected_amount' => $validatedData['collected_amount'],
            'collection_date' => $validatedData['collection_date'],
            'payment_method_id' => $importConfig['payment_method_id'],
            'collection_type' => $importConfig['collection_type'],
            'payment_type' => $importConfig['payment_type'],
            'receipt_number' => $this->generateReceiptNumber(),
            'status' => 'pending',
            'added_by' => $validatedData['assigned_user_id'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Receipt Number
    |--------------------------------------------------------------------------
    */
    protected function generateReceiptNumber(): string
    {
        $prefix = 'RC';
        $date = now()->format('Ymd');
        $lastCollection = Collection::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCollection ?
            (int) substr($lastCollection->receipt_number, -4) + 1 : 1;

        return $prefix.$date.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Find And Extract Data
    |--------------------------------------------------------------------------
    */
    protected function findAndExtractData(array $allRows): ?array
    {
        $headerRowIndex = null;
        $headersRaw = [];

        foreach ($allRows as $index => $row) {
            if (is_null($row)) {
                continue;
            }

            $nonEmptyCount = count(array_filter($row, fn ($cell) => ! is_null($cell) && trim($cell) !== ''));

            if ($nonEmptyCount >= 3) {
                $headerRowIndex = $index;
                $headersRaw = $row;
                break;
            }
        }

        if ($headerRowIndex === null || empty($headersRaw)) {
            return null;
        }

        $dataRowsCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($allRows); $i++) {
            $row = $allRows[$i];
            if (! is_null($row)) {
                $nonEmptyCount = count(array_filter($row, fn ($cell) => ! is_null($cell) && trim($cell) !== ''));
                if ($nonEmptyCount > 0) {
                    $dataRowsCount++;
                }
            }
        }

        $dataRows = array_slice($allRows, $headerRowIndex + 1);
        $dataRows = array_filter($dataRows, function ($row) {
            return ! is_null($row) && ! empty(array_filter($row, fn ($cell) => ! is_null($cell) && $cell !== ''));
        });

        $result = [
            'headers' => $headersRaw,
            'data' => array_values($dataRows),
            'total_rows' => $dataRowsCount,
            'header_row_index' => $headerRowIndex,
        ];

        return $result;
    }
}

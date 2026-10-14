<?php

namespace App\Services\Tenants\BusinessCenter\Customer\BulkUpdate;

use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\BulkUpdateRequest;
use App\Jobs\Tenants\BusinessCenter\Customer\BulkUpdate\ProcessCustomerBulkExclusion;
use App\Jobs\Tenants\BusinessCenter\Customer\BulkUpdate\ProcessCustomerBulkUpdate;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerBulkUpdateService
{
    public function storeTemporaryFile(UploadedFile $file): string
    {
        return $file->store('temp_updates');
    }

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
    | Get Updatable Fields For Portfolio
    |--------------------------------------------------------------------------
    */
    public function getUpdatableFieldsForPortfolio(int $portfolioId): array
    {
        $portfolio = Portfolio::with('fields')->findOrFail($portfolioId);

        $baseFields = [];

        if ($portfolio->has_split_names) {
            $baseFields = [
                'first_name' => 'الاسم الأول',
                'father_name' => 'اسم الأب',
                'grandfather_name' => 'اسم الجد',
                'family_name' => 'اسم العائلة',
            ];
        } else {
            $baseFields = [
                'name' => 'إسم العميل',
            ];
        }

        $otherBaseFields = [
            'customer_type' => 'نوع العميل',
            'identity_number' => 'رقم الهوية',
            'debt_amount' => 'مبلغ المديونية',
            'employee_number' => 'الرقم الوظيفي',
            'phone' => 'رقم الهاتف',
            'account_number' => 'رقم الحساب',
            // ✅ إضافة الأرقام الإضافية
            'additional_phone_1' => 'رقم الهاتف الإضافي 1',
            'additional_phone_2' => 'رقم الهاتف الإضافي 2',
            'additional_phone_3' => 'رقم الهاتف الإضافي 3',
            'additional_phone_4' => 'رقم الهاتف الإضافي 4',
            'additional_phone_5' => 'رقم الهاتف الإضافي 5',
        ];

        $baseFields = array_merge($baseFields, $otherBaseFields);
        $dynamicFields = $portfolio->fields->pluck('field_name', 'id')->toArray();
        $formattedDynamicFields = [];
        foreach ($dynamicFields as $id => $name) {
            $formattedDynamicFields['dynamic_'.$id] = $name;
        }

        return [
            'base_fields' => $baseFields,
            'dynamic_fields' => $formattedDynamicFields,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Template
    |--------------------------------------------------------------------------
    */
    public function generateTemplate(array $data): array
    {
        $allFields = $this->getUpdatableFieldsForPortfolio($data['portfolio_id']);
        $headers = [];

        $headers[$data['match_key']] = $data['match_key'] === 'account_number' ? 'رقم الحساب' : 'رقم الهوية';

        foreach ($data['update_fields'] as $fieldKey) {
            if (isset($allFields['base_fields'][$fieldKey])) {
                $headers[$fieldKey] = $allFields['base_fields'][$fieldKey];
            } elseif (isset($allFields['dynamic_fields'][$fieldKey])) {
                $headers[$fieldKey] = $allFields['dynamic_fields'][$fieldKey];
            }
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $columnIndex = 1;
        foreach ($headers as $key => $header) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);

            // إضافة تعليقات توضيحية للحقول الخاصة
            if ($key === 'customer_type') {
                $sheet->setCellValueByColumnAndRow($columnIndex, 2, 'فردي أو شركة');
                $sheet->getStyleByColumnAndRow($columnIndex, 2)->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyleByColumnAndRow($columnIndex, 2)->getFont()->getColor()->setRGB('666666');
            } elseif ($key === 'employee_number') {
                $sheet->setCellValueByColumnAndRow($columnIndex, 2, 'الرقم الوظيفي للموظف');
                $sheet->getStyleByColumnAndRow($columnIndex, 2)->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyleByColumnAndRow($columnIndex, 2)->getFont()->getColor()->setRGB('666666');
            }

            $columnIndex++;
        }

        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if (! Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }

        $fileName = 'customer_update_template_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/temp/'.$fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return ['path' => $path, 'name' => $fileName];
    }

    /*
    |--------------------------------------------------------------------------
    | Analyze File Headers
    |--------------------------------------------------------------------------
    */
    public function analyzeFileHeaders(UploadedFile $file): array
    {
        $tempPath = $file->store('temp_analysis');
        $fullPath = storage_path('app/'.$tempPath);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $firstRow = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1')[0];

            $headers = array_map(function ($header) {
                return trim($header ?? '');
            }, $firstRow);

            $headers = array_filter($headers, function ($header) {
                return ! empty($header);
            });

            Storage::delete($tempPath);

            return array_values($headers);
        } catch (\Exception $e) {
            Storage::delete($tempPath);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Bulk Update Process
    |--------------------------------------------------------------------------
    */
    public function queueBulkUpdate(array $data, array $fieldMappings = []): string
    {
        $tempPath = $data['temp_file_path'];
        $jobId = uniqid('bulk_update_');

        Cache::put("bulk_update_progress_{$jobId}", [
            'requested_by' => auth('tenant')->id(),
            'status' => 'queued',
            'processed' => 0,
            'total' => 0,
            'updated' => 0,
            'failed' => 0,
            'started_at' => now(),
        ], 3600);

        ProcessCustomerBulkUpdate::dispatch([
            'job_id' => $jobId,
            'temp_file_path' => $tempPath,
            'portfolio_id' => $data['portfolio_id'],
            'match_key' => $data['match_key'],
            'field_mappings' => $fieldMappings,
            'user_id' => auth('tenant')->id(),
        ]);

        return $jobId;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Progress Status
    |--------------------------------------------------------------------------
    */
    public function getProgressStatus(string $jobId, int $userId): array
    {
        $progress = Cache::get("bulk_update_progress_{$jobId}");

        if (! is_array($progress) || (int) ($progress['requested_by'] ?? 0) !== $userId) {
            return [
                'status' => 'not_found',
                'processed' => 0,
                'total' => 0,
                'updated' => 0,
                'failed' => 0,
            ];
        }

        return $progress;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Error Report
    |--------------------------------------------------------------------------
    */
    public function generateErrorReport(array $errors): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        $sheet->setCellValue('A1', 'تقرير أخطاء التحديث المجمع للعملاء');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'تاريخ التنفيذ: '.now()->format('Y-m-d H:i:s'));
        $sheet->setCellValue('A3', 'عدد الأخطاء: '.count($errors));

        $sheet->setCellValue('A5', 'رقم الصف');
        $sheet->setCellValue('B5', 'مفتاح المطابقة');
        $sheet->setCellValue('C5', 'نوع الخطأ');
        $sheet->setCellValue('D5', 'تفاصيل الخطأ');

        $headerRange = 'A5:D5';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E3F2FD');

        $row = 6;
        foreach ($errors as $error) {
            $sheet->setCellValue('A'.$row, $error['row']);
            $sheet->setCellValue('B'.$row, $error['match_value'] ?? '');

            if (strpos($error['message'], 'سدادات مسجلة') !== false) {
                $sheet->setCellValue('C'.$row, 'مديونية محظورة');
            } elseif (strpos($error['message'], 'الرقم الوظيفي') !== false) {
                $sheet->setCellValue('C'.$row, 'رقم وظيفي غير موجود');
            } elseif (strpos($error['message'], 'نوع العميل') !== false) {
                $sheet->setCellValue('C'.$row, 'نوع عميل غير صحيح');
            } else {
                $sheet->setCellValue('C'.$row, 'خطأ عام');
            }

            $sheet->setCellValue('D'.$row, $error['message']);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(50);

        $fileName = 'bulk_update_errors_'.now()->format('YmdHis').'.xlsx';
        $filePath = 'reports/'.$fileName;

        if (! Storage::exists('reports')) {
            Storage::makeDirectory('reports');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/'.$filePath));

        return $filePath;
    }

    public function rememberErrorReportOwner(string $path, int $userId): void
    {
        Cache::put(
            BulkUpdateRequest::errorReportOwnerKey($path),
            $userId,
            now()->addHours(2),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolios For Company
    |--------------------------------------------------------------------------
    */
    public function getPortfoliosForCompany(int $companyId): Collection
    {
        return Portfolio::where('company_id', $companyId)
            ->active()
            ->get(['id', 'name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate File Row Count
    |--------------------------------------------------------------------------
    */
    public function validateFileRowCount(UploadedFile $file): array
    {
        $tempPath = $file->store('temp_analysis');
        $fullPath = storage_path('app/'.$tempPath);

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $totalRows = $highestRow - 1;

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet, $reader);
            gc_collect_cycles();

            Storage::delete($tempPath);

            if ($totalRows > 50000) {
                return [
                    'valid' => false,
                    'total_rows' => $totalRows,
                    'message' => sprintf(
                        'عدد الصفوف (%s) يتجاوز الحد الأقصى المسموح به (50,000 صف). يرجى تقسيم الملف إلى ملفات أصغر.',
                        number_format($totalRows)
                    ),
                ];
            }

            return [
                'valid' => true,
                'total_rows' => $totalRows,
            ];
        } catch (\Exception $e) {
            Storage::delete($tempPath);

            return [
                'valid' => false,
                'message' => 'فشل في التحقق من عدد الصفوف: '.$e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Exclusion Template
    |--------------------------------------------------------------------------
    */
    public function generateExclusionTemplate(array $data): array
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        $keyLabel = $data['exclusion_key'] === 'account_number' ? 'رقم الحساب' : 'رقم الهوية';

        $sheet->setCellValue('A1', $keyLabel);

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'], // أحمر للاستبعاد
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->setCellValue('A2', 'مثال: 123456');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('666666');

        $sheet->getColumnDimension('A')->setAutoSize(true);

        if (! Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }

        $fileName = 'customer_exclusion_template_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/temp/'.$fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return ['path' => $path, 'name' => $fileName];
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Bulk Exclusion Process
    |--------------------------------------------------------------------------
    */
    public function queueBulkExclusion(array $data): string
    {
        $tempPath = $data['temp_file_path'];
        $jobId = uniqid('bulk_exclusion_');

        Cache::put("bulk_exclusion_progress_{$jobId}", [
            'requested_by' => auth('tenant')->id(),
            'status' => 'queued',
            'processed' => 0,
            'total' => 0,
            'excluded' => 0,
            'failed' => 0,
            'started_at' => now(),
        ], 3600);

        ProcessCustomerBulkExclusion::dispatch([
            'job_id' => $jobId,
            'temp_file_path' => $tempPath,
            'portfolio_id' => $data['portfolio_id'],
            'exclusion_key' => $data['exclusion_key'],
            'user_id' => auth('tenant')->id(),
        ]);

        return $jobId;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Exclusion Progress Status
    |--------------------------------------------------------------------------
    */
    public function getExclusionProgressStatus(string $jobId, int $userId): array
    {
        $progress = Cache::get("bulk_exclusion_progress_{$jobId}");

        if (! is_array($progress) || (int) ($progress['requested_by'] ?? 0) !== $userId) {
            return [
                'status' => 'not_found',
                'processed' => 0,
                'total' => 0,
                'excluded' => 0,
                'failed' => 0,
            ];
        }

        return $progress;
    }
}

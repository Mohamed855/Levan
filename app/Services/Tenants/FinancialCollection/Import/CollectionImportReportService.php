<?php

namespace App\Services\Tenants\FinancialCollection\Import;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Storage;

class CollectionImportReportService
{
    /*
    |--------------------------------------------------------------------------
    | Generate Import Error Report
    |--------------------------------------------------------------------------
    | Creates a detailed Excel report of all rows that failed during import.
    */
    public function generateImportErrorReport(array $reportData): string
    {
        if (!isset($reportData['errors']) || !is_array($reportData['errors'])) {
            return '';
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setRightToLeft(true);

            // إعداد العناوين
            $sheet->setCellValue('A1', 'تقرير أخطاء استيراد التحصيلات');
            $sheet->mergeCells('A1:C1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // عناوين الأعمدة
            $sheet->setCellValue('A3', 'رقم الصف');
            $sheet->setCellValue('B3', 'نوع الخطأ');
            $sheet->setCellValue('C3', 'تفاصيل الخطأ');

            // تنسيق العناوين
            $headerRange = 'A3:C3';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E3F2FD');

            $row = 4;
            foreach ($reportData['errors'] as $error) {
                $sheet->setCellValue('A' . $row, $error['row']);

                // تصنيف نوع الخطأ وتلوينه
                $errorText = implode(' | ', $error['errors']);
                if (strpos($errorText, 'ليس له مسؤول علاقات') !== false) {
                    $sheet->setCellValue('B' . $row, 'عدم وجود مسؤول علاقات');
                    $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('FF0000');
                    $sheet->getStyle('B' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFEBEE');
                } elseif (strpos($errorText, 'غير موجود') !== false) {
                    $sheet->setCellValue('B' . $row, 'عميل غير موجود');
                    $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('FF5722');
                } elseif (strpos($errorText, 'غير صالح') !== false) {
                    $sheet->setCellValue('B' . $row, 'خطأ في البيانات');
                    $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('FF9800');
                } else {
                    $sheet->setCellValue('B' . $row, 'خطأ عام');
                }

                $sheet->setCellValue('C' . $row, $errorText);
                $row++;
            }

            // إضافة إحصائيات
            $totalErrors = count($reportData['errors']);
            $noManagerErrors = array_filter($reportData['errors'], function ($error) {
                return strpos(implode(' | ', $error['errors']), 'ليس له مسؤول علاقات') !== false;
            });

            $sheet->setCellValue('A' . ($row + 2), 'إحصائيات الأخطاء:');
            $sheet->setCellValue('A' . ($row + 3), "إجمالي الأخطاء: {$totalErrors}");
            $sheet->setCellValue('A' . ($row + 4), "عملاء بدون مسؤول علاقات: " . count($noManagerErrors));

            // تنسيق الأعمدة
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(50);

            $fileName = 'error_report_' . now()->format('YmdHis') . '.xlsx';
            $filePath = storage_path('app/reports/' . $fileName);

            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);

            return 'reports/' . $fileName;
        } catch (\Exception $e) {
            return '';
        }
    }
}

<?php

namespace App\Exports\Tenants\FinancialCollection\Reconciliation;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummaryReportExport implements WithColumnWidths, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $reconciliation;

    public function __construct($reconciliation)
    {
        $this->reconciliation = $reconciliation;
    }

    public function headings(): array
    {
        return [
            ['تقرير مطابقة السدادات - ملخص شامل'],
            [],
            ['الشركة:', $this->reconciliation->company->name],
            ['المحفظة:', $this->reconciliation->portfolio->name],
            ['شهر المطابقة:', $this->reconciliation->reconciliation_month->format('Y-m')],
            ['تاريخ المعالجة:', $this->reconciliation->processed_at?->format('Y-m-d H:i')],
            [],
            ['الإحصائيات العامة'],
            [],
            ['الفئة', 'العدد', 'المبلغ', 'النسبة'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            8 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            10 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 15, 'C' => 20, 'D' => 15];
    }

    public function title(): string
    {
        return 'الملخص الشامل';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('A8:D8');

                $data = [
                    ['إجمالي الصفوف', $this->reconciliation->total_rows, number_format($this->reconciliation->total_amount, 2), '100%'],
                    ['متطابق', $this->reconciliation->matched_count, number_format($this->reconciliation->matched_amount, 2), round(($this->reconciliation->matched_count / max($this->reconciliation->total_rows, 1)) * 100, 1).'%'],
                    ['غير مسجل', $this->reconciliation->unmatched_count, number_format($this->reconciliation->unmatched_amount, 2), round(($this->reconciliation->unmatched_count / max($this->reconciliation->total_rows, 1)) * 100, 1).'%'],
                    ['اختلافات', $this->reconciliation->discrepancy_count, number_format($this->reconciliation->discrepancy_amount, 2), round(($this->reconciliation->discrepancy_count / max($this->reconciliation->total_rows, 1)) * 100, 1).'%'],
                    ['أخطاء', $this->reconciliation->error_count, '-', round(($this->reconciliation->error_count / max($this->reconciliation->total_rows, 1)) * 100, 1).'%'],
                    ['سدادات النظام فقط', $this->reconciliation->system_only_count, '-', '-'],
                ];

                $sheet->fromArray($data, null, 'A11');

                $lastRow = 10 + count($data);
                $sheet->getStyle('A1:D'.$lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
                ]);
            },
        ];
    }
}

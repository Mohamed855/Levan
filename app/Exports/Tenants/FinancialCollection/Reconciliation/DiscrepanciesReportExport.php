<?php

namespace App\Exports\Tenants\FinancialCollection\Reconciliation;

use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DiscrepanciesReportExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function collection(): Enumerable
    {
        return $this->items;
    }

    public function headings(): array
    {
        return ['#', 'رقم الحساب', 'العميل', 'مبلغ الملف', 'مبلغ النظام', 'الفرق', 'تاريخ الملف', 'تاريخ النظام'];
    }

    public function map($item): array
    {
        static $counter = 1;

        return [
            $counter++,
            $item->account_number,
            $item->customer?->name ?? 'غير موجود',
            number_format($item->file_amount, 2),
            number_format($item->system_amount, 2),
            number_format($item->amount_difference, 2),
            $item->file_date->format('Y-m-d'),
            $item->system_date?->format('Y-m-d') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 18, 'C' => 25, 'D' => 15, 'E' => 15, 'F' => 12, 'G' => 15, 'H' => 15];
    }

    public function title(): string
    {
        return 'الاختلافات';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $lastRow = $this->items->count() + 1;
                $sheet->getStyle('A1:H'.$lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
                ]);
            },
        ];
    }
}

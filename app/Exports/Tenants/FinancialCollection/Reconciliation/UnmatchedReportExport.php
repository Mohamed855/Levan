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

class UnmatchedReportExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return ['#', 'رقم الحساب', 'اسم العميل', 'المبلغ', 'التاريخ', 'الموظف المسؤول'];
    }

    public function map($item): array
    {
        static $counter = 1;

        return [
            $counter++,
            $item->account_number,
            $item->customer?->name ?? 'غير موجود',
            number_format($item->file_amount, 2),
            $item->file_date->format('Y-m-d'),
            $item->customer?->relationshipManager?->name ?? '-',
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
        return ['A' => 8, 'B' => 20, 'C' => 25, 'D' => 15, 'E' => 15, 'F' => 20];
    }

    public function title(): string
    {
        return 'السدادات غير المسجلة';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $lastRow = $this->items->count() + 1;
                $sheet->getStyle('A1:F'.$lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
                ]);
            },
        ];
    }
}

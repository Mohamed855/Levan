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

class SystemOnlyReportExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $collections;

    public function __construct($collections)
    {
        $this->collections = $collections;
    }

    public function collection(): Enumerable
    {
        return $this->collections;
    }

    public function headings(): array
    {
        return ['#', 'رقم السند', 'رقم الحساب', 'العميل', 'المبلغ', 'التاريخ', 'الموظف'];
    }

    public function map($collection): array
    {
        static $counter = 1;

        return [
            $counter++,
            $collection->receipt_number ?? '-',
            $collection->customer->account_number,
            $collection->customer->name,
            number_format($collection->collected_amount, 2),
            $collection->collection_date->format('Y-m-d'),
            $collection->addedBy->name,
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
        return ['A' => 8, 'B' => 18, 'C' => 18, 'D' => 25, 'E' => 15, 'F' => 15, 'G' => 20];
    }

    public function title(): string
    {
        return 'سدادات النظام فقط';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $lastRow = $this->collections->count() + 1;
                $sheet->getStyle('A1:G'.$lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
                ]);
            },
        ];
    }
}

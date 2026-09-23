<?php

namespace App\Exports\Tenants\FinancialCollection\Reconciliation;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EmployeePerformanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $stats;

    public function __construct($stats)
    {
        $this->stats = collect($stats);
    }

    public function collection()
    {
        return $this->stats;
    }

    public function headings(): array
    {
        return ['#', 'الموظف', 'سدادات مسجلة', 'سدادات مباشرة', 'إجمالي', 'نسبة التسجيل'];
    }

    public function map($stat): array
    {
        static $counter = 1;
        return [
            $counter++,
            $stat['manager'],
            $stat['matched'],
            $stat['unmatched'],
            $stat['total'],
            $stat['percentage'] . '%'
        ];
    }

    public function styles(Worksheet $sheet)
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
        return ['A' => 8, 'B' => 25, 'C' => 18, 'D' => 18, 'E' => 15, 'F' => 18];
    }

    public function title(): string
    {
        return 'أداء الموظفين';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $lastRow = $this->stats->count() + 1;
                $sheet->getStyle('A1:F' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
                ]);
            },
        ];
    }
}
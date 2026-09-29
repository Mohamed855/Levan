<?php

namespace App\Exports\Tenants\BusinessCenter\Customer\Import\Template;

use App\Enums\Tenants\CustomerImport\CustomerImportFields;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerImportTemplateExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    public function __construct(protected Portfolio $portfolio) {}

    public function title(): string // الاسم في التبويب داخل الملف
    {
        return 'نموذج استيراد العملاء';
    }

    public function headings(): array
    {
        $requiredHeaders = CustomerImportFields::requiredHeaders($this->portfolio->has_split_names);
        $optionalHeaders = CustomerImportFields::optionalHeaders($this->portfolio->has_split_names);

        $dynamicFields =  $this->portfolio->portfolioFields->map(function ($field) {
            return $field->field_name . ($field->is_required ? ' *' : '');
        })->toArray();



        $dynamicFields = $this->portfolio->portfolioFields->map(function ($field) {
            return $field->field_name . ($field->is_required ? ' *' : '');
        })->toArray();  //  الحقول الديناميكية من المحفظة

        return array_merge($requiredHeaders, $optionalHeaders, $dynamicFields);
    }

    public function collection()
    {
        // لا نحتاج لبيانات حقيقية، فقط نعيد مصفوفة فارغة لإنشاء ملف فارغ
        // مع العناوين التي تم تعريفها في دالة headings()
        return collect([]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();

                // اتجاه عربي
                $sheet->setRightToLeft(true);

                // تثبيت صف العناوين
                $sheet->freezePane('A2');

                // تنسيق بسيط لصف العناوين
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 11,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2a8d60'],
                    ],
                    'alignment' => [
                        'horizontal'    => Alignment::HORIZONTAL_CENTER,
                        'vertical'      => Alignment::VERTICAL_CENTER,
                        'wrapText'      => true,
                    ],
                ]);

                // عرض أعمدة بسيط وثابت
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                for ($i = 1; $i <= $highestColumnIndex; $i++) {
                    $column = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($column)->setWidth(22);
                }

                // ارتفاع صف العناوين
                $sheet->getRowDimension(1)->setRowHeight(33);
            },
        ];
    }
}

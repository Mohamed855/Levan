<?php

namespace App\Exports\Tenants\Reports\CustomerFollow;

use Illuminate\Support\LazyCollection;
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
use Carbon\Carbon;

class CustomerFollowReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $query;
    protected $filters;
    protected $selectedFields;
    protected $totalCount;
    protected $rowCount    = 0;
    protected $counter     = 0;
    protected $progressCallback;
    protected int $startAfterId  = 0;
    protected int $maxRows       = 0;
    protected int $lastExportedId = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(
        $query,
        $filters,
        $selectedFields    = [],
        $totalCount        = 0,
        $progressCallback  = null,
        int $startAfterId  = 0,
        int $maxRows       = 0
    ) {
        $this->query            = $query;
        $this->filters          = $filters;
        $this->selectedFields   = $selectedFields;
        $this->totalCount       = $totalCount;
        $this->progressCallback = $progressCallback;
        $this->startAfterId     = $startAfterId;
        $this->maxRows          = $maxRows;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection()
    {
        if ($this->query instanceof \Illuminate\Support\Collection) {
            return $this->query;
        }

        $query        = $this->query;
        $totalCount   = $this->totalCount;
        $callback     = $this->progressCallback;
        $startAfterId = $this->startAfterId;
        $maxRows      = $this->maxRows;

        return LazyCollection::make(function () use ($query, $totalCount, $callback, $startAfterId, $maxRows) {
            $chunkSize = 500;
            $lastId    = $startAfterId;
            $processed = 0;

            while (true) {
                $fetchLimit = ($maxRows > 0)
                    ? min($chunkSize, $maxRows - $processed)
                    : $chunkSize;

                if ($fetchLimit <= 0) {
                    break;
                }

                $chunk = (clone $query)
                    ->where('customer_follows.id', '>', $lastId)
                    ->orderBy('customer_follows.id')
                    ->limit($fetchLimit)
                    ->get();

                if ($chunk->isEmpty()) {
                    break;
                }

                $lastId     = $chunk->last()->id;
                $chunkCount = $chunk->count();

                foreach ($chunk as $item) {
                    yield $item;
                }

                $processed += $chunkCount;

                if ($callback && $totalCount > 0) {
                    $progress = 10 + (($processed / $totalCount) * 80);
                    $callback((int) round($progress), "معالجة {$processed} من {$totalCount} متابعة...");
                }

                unset($chunk);
                gc_collect_cycles();

                if ($maxRows > 0 && $processed >= $maxRows) {
                    break;
                }
            }

            // جميع السجلات جُمعت — PhpSpreadsheet يكتب الملف الآن
            if ($callback) {
                $callback(95, 'جاري كتابة الملف...');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Last Exported ID (for multi-part cursor)
    |--------------------------------------------------------------------------
    */
    public function getLastExportedId(): int
    {
        return $this->lastExportedId;
    }

    /*
    |--------------------------------------------------------------------------
    | Headings
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        $headings = ['م'];

        $fieldLabels = [
            'id'                        => 'رقم المتابعة',
            'customer_name'             => 'اسم العميل',
            'customer_account_number'   => 'رقم الحساب',
            'customer_identity_number'  => 'رقم الهوية',
            'company_name'              => 'الشركة',
            'portfolio_name'            => 'المحفظة',
            'contact_method'            => 'طريقة الاتصال',
            'contact_location'          => 'موقع الاتصال',
            'contacted_person'          => 'الشخص المتصل به',
            'contacted_person_name'     => 'اسم الشخص المتصل به',
            'customer_status'           => 'حالة العميل',
            'contact_result'            => 'نتيجة الاتصال',
            'notes'                     => 'ملاحظات',
            'created_by'                => 'أنشأ بواسطة',
            'created_at'                => 'تاريخ الإنشاء',
            'payment_amount'            => 'المبلغ المسدد (تم السداد)',
            'payment_date'              => 'تاريخ السداد (تم السداد)',
            'payment_type'              => 'نوع السداد (تم السداد)',
            'partial_payment_amount'    => 'المبلغ المسدد (سداد جزئي)',
            'partial_payment_date'      => 'تاريخ السداد (سداد جزئي)',
            'promise_amount'            => 'المبلغ الموعود به',
            'promise_date'              => 'تاريخ السداد الموعود',
            'callback_date'             => 'تاريخ المعاودة',
            'callback_reason'           => 'سبب المعاودة',
            'documents_date'            => 'تاريخ طلب المستندات',
            'documents_reason'          => 'سبب طلب المستندات',
            'objection_reason'          => 'سبب الاعتراض',
            'recovery_reason'           => 'سبب الاسترداد',
        ];

        foreach ($this->selectedFields as $field) {
            $headings[] = $fieldLabels[$field] ?? $field;
        }

        return $headings;
    }

    /*
    |--------------------------------------------------------------------------
    | Map
    |--------------------------------------------------------------------------
    */
    public function map($follow): array
    {
        $this->lastExportedId = $follow->id;

        $row = [++$this->counter];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                case 'id':
                    $row[] = $follow->id;
                    break;
                case 'customer_name':
                    $row[] = $follow->customer->name ?? 'غير محدد';
                    break;
                case 'customer_account_number':
                    $row[] = $follow->customer->account_number ?? 'غير محدد';
                    break;
                case 'customer_identity_number':
                    $row[] = $follow->customer->identity_number ?? 'غير محدد';
                    break;
                case 'company_name':
                    $row[] = $follow->customer->company->name ?? 'غير محدد';
                    break;
                case 'portfolio_name':
                    $row[] = $follow->customer->portfolio->name ?? 'غير محدد';
                    break;
                case 'contact_method':
                    $row[] = $follow->contactMethod->name ?? 'غير محدد';
                    break;
                case 'contact_location':
                    $row[] = $follow->contact_location ?: 'غير محدد';
                    break;
                case 'contacted_person':
                    $row[] = $follow->getContactedPersonLabelAttribute() ?: 'غير محدد';
                    break;
                case 'contacted_person_name':
                    $row[] = $follow->contacted_person_name ?: 'غير محدد';
                    break;
                case 'customer_status':
                    $row[] = $follow->customerStatus->name ?? 'غير محدد';
                    break;
                case 'contact_result':
                    $row[] = $follow->contactResult->name ?? 'غير محدد';
                    break;
                case 'notes':
                    $row[] = $follow->notes ?: 'غير محدد';
                    break;
                case 'created_by':
                    $row[] = $follow->createdBy->name ?? 'غير محدد';
                    break;
                case 'created_at':
                    $row[] = $follow->created_at->format('Y-m-d H:i');
                    break;
                case 'payment_amount':
                    $row[] = $follow->fullPaymentDetails
                        ? number_format($follow->fullPaymentDetails->payment_amount, 2) . ' ريال'
                        : 'غير محدد';
                    break;
                case 'payment_date':
                    $row[] = $follow->fullPaymentDetails
                        ? Carbon::parse($follow->fullPaymentDetails->payment_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'payment_type':
                    $row[] = $follow->fullPaymentDetails
                        ? $this->getPaymentTypeLabel($follow->fullPaymentDetails->payment_type)
                        : 'غير محدد';
                    break;
                case 'partial_payment_amount':
                    $row[] = $follow->partialPaymentDetails
                        ? number_format($follow->partialPaymentDetails->partial_payment_amount, 2) . ' ريال'
                        : 'غير محدد';
                    break;
                case 'partial_payment_date':
                    $row[] = $follow->partialPaymentDetails
                        ? Carbon::parse($follow->partialPaymentDetails->partial_payment_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'promise_amount':
                    $row[] = $follow->promisePaymentDetails
                        ? number_format($follow->promisePaymentDetails->promise_amount, 2) . ' ريال'
                        : 'غير محدد';
                    break;
                case 'promise_date':
                    $row[] = $follow->promisePaymentDetails
                        ? Carbon::parse($follow->promisePaymentDetails->promise_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'callback_date':
                    $row[] = $follow->callbackDetails
                        ? Carbon::parse($follow->callbackDetails->callback_date)->format('Y-m-d H:i')
                        : 'غير محدد';
                    break;
                case 'callback_reason':
                    $row[] = $follow->callbackDetails
                        ? $this->getCallbackReasonLabel($follow->callbackDetails->callback_reason)
                        : 'غير محدد';
                    break;
                case 'documents_date':
                    $row[] = $follow->documentRequestDetails
                        ? Carbon::parse($follow->documentRequestDetails->documents_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'documents_reason':
                    $row[] = $follow->documentRequestDetails
                        ? ($follow->documentRequestDetails->documentReason->name ?? 'غير محدد')
                        : 'غير محدد';
                    break;
                case 'objection_reason':
                    $row[] = $follow->objectionDetails
                        ? $follow->objectionDetails->objection_reason
                        : 'غير محدد';
                    break;
                case 'recovery_reason':
                    $row[] = $follow->recoveryDetails
                        ? $this->getRecoveryReasonLabel($follow->recoveryDetails->recovery_reason)
                        : 'غير محدد';
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }

        $this->rowCount++;
        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Styles
    |--------------------------------------------------------------------------
    */
    public function styles(Worksheet $sheet)
    {
        $lastColumn       = count($this->selectedFields) + 1;
        $lastColumnLetter = $this->getColumnLetter($lastColumn);
        $lastRow          = $this->rowCount + 1;

        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '1F4E78'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            "A2:{$lastColumnLetter}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Column Widths
    |--------------------------------------------------------------------------
    */
    public function columnWidths(): array
    {
        $widths = ['A' => 8];

        $fieldWidths = [
            'id'                     => 12,
            'customer_name'          => 25,
            'contact_method'         => 18,
            'contact_location'       => 18,
            'contacted_person'       => 18,
            'contacted_person_name'  => 20,
            'next_contact_datetime'  => 20,
            'customer_status'        => 18,
            'contact_result'         => 20,
            'notes'                  => 25,
            'created_by'             => 20,
            'created_at'             => 18,
            'payment_amount'         => 20,
            'payment_date'           => 18,
            'payment_type'           => 18,
            'partial_payment_amount' => 20,
            'partial_payment_date'   => 18,
            'promise_amount'         => 20,
            'promise_date'           => 18,
            'callback_date'          => 20,
            'callback_reason'        => 25,
            'documents_date'         => 18,
            'documents_reason'       => 25,
            'objection_reason'       => 25,
            'recovery_reason'        => 25,
        ];

        $columnIndex = 1;
        foreach ($this->selectedFields as $field) {
            $columnLetter          = $this->getColumnLetter($columnIndex + 1);
            $widths[$columnLetter] = $fieldWidths[$field] ?? 15;
            $columnIndex++;
        }

        return $widths;
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $lastColumn       = count($this->selectedFields) + 1;
                $lastColumnLetter = $this->getColumnLetter($lastColumn);
                $lastRow          = $this->rowCount + 1;

                $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->setAutoFilter("A1:{$lastColumnLetter}{$lastRow}");
                $sheet->setRightToLeft(true);
            },
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */
    public function title(): string
    {
        return 'تقرير متابعات العملاء';
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function getColumnLetter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $letter       = chr(65 + ($columnNumber % 26)) . $letter;
            $columnNumber = intval($columnNumber / 26);
        }
        return $letter;
    }

    private function getPaymentTypeLabel(string $type): string
    {
        return [
            'bank_transfer' => 'تحويل بنكي',
            'branch'        => 'عن طريق الفرع',
            'settlement'    => 'تسوية',
            'receipt'       => 'إيصال',
        ][$type] ?? $type;
    }

    private function getCallbackReasonLabel(string $reason): string
    {
        return [
            'client_busy'         => 'أفاد العميل بأنه مشغول حالياً',
            'branch_review'       => 'مراجعة فرع الشركة للتأكد من المديونية',
            'branch_verification' => 'مراجعة فرع الشركة للتحقق من الخصم',
        ][$reason] ?? $reason;
    }

    private function getRecoveryReasonLabel(string $reason): string
    {
        return [
            'valid_print'      => 'برنت سارية',
            'has_license'      => 'يملك رخصة',
            'star_report'      => 'تقرير نجم بعد التعديل',
            'traffic_decision' => 'قرار لجنة مرورية',
            'court_deed'       => 'صك محكمة',
        ][$reason] ?? $reason;
    }
}

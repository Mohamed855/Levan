<?php

namespace App\DataTables\Tenants\FinancialCollection\Target;

use App\DataTables\Abstract\AbstractDataTable;
use App\Services\Tenants\FinancialCollection\Target\CollectionTargetService;
use Carbon\Carbon;

class UserTargetsDataTable extends AbstractDataTable
{
    private $route = "financial-collections.collection-targets";
    private $userId;
    private $collectionTargetService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(CollectionTargetService $collectionTargetService)
    {
        $this->collectionTargetService = $collectionTargetService;
    }

    /*
    |--------------------------------------------------------------------------
    | Set User ID
    |--------------------------------------------------------------------------
    */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Build DataTable (Override - نفس أسلوب Portfolios و Companies)
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $datatable = datatables()->collection($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Get Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return "user-targets-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return $this->collectionTargetService->getUserAllTargets($this->userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'DT_RowIndex',           'name' => 'DT_RowIndex',            'title' => 'مـ', 'orderable' => false, 'searchable' => false, 'width' => '30px',],
            ['data' => 'target_type_badge',     'name' => 'target_type_badge',      'title' => 'نوع الهدف',],
            ['data' => 'target_period',         'name' => 'target_period',          'title' => 'الفترة', 'orderable' => false,],
            ['data' => 'target_amount_display', 'name' => 'target_amount_display',  'title' => 'مبلغ الهدف',],
            ['data' => 'collected_amount',      'name' => 'collected_amount',       'title' => 'المحصل', 'orderable' => false, 'searchable' => false,],
            ['data' => 'remaining_amount',      'name' => 'remaining_amount',       'title' => 'المتبقي', 'orderable' => false, 'searchable' => false,],
            ['data' => 'progress_bar',          'name' => 'progress_bar',           'title' => 'التقدم', 'orderable' => false, 'searchable' => false, 'width' => '200px'],
            // ['data' => 'status',                'name' => 'status',                 'title' => 'الحالة'],
            ['data' => 'created_at_formatted',  'name' => 'created_at_formatted',   'title' => 'تاريخ الإضافة',],
            ['data' => 'actions',               'name' => 'actions',                'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false,],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Modifications
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('target_type_badge', function ($row) {
                $typeClass = match ($row->target_type) {
                    'monthly' => 'bg-label-info',
                    'yearly' => 'bg-label-primary',
                    'custom' => 'bg-label-warning',
                    default => 'bg-label-secondary'
                };
                return '<span class="badge ' . $typeClass . ' px-3 py-2">' . e($row->target_type_label) . '</span>';
            })

            ->addColumn('target_period', function ($row) {
                return '<span class="fw-medium">' . e($this->getTargetPeriodLabel($row)) . '</span>';
            })

            ->addColumn('target_amount_display', function ($row) {
                return '<span class="fw-bold">' . number_format($row->target_amount, 0) . '</span>';
            })

            ->addColumn('collected_amount', function ($row) {
                $progress = $this->collectionTargetService->calculateTargetProgress($row);
                return '<span class="fw-medium text-success">' . number_format($progress['collected_amount'], 0) . '</span>';
            })

            ->addColumn('remaining_amount', function ($row) {
                $progress = $this->collectionTargetService->calculateTargetProgress($row);
                return '<span class="fw-medium text-danger">' . number_format($progress['remaining_amount'], 0) . '</span>';
            })

            ->addColumn('progress_bar', function ($row) {
                $progress   = $this->collectionTargetService->calculateTargetProgress($row);
                $percentage = min((float) $progress['percentage'], 100);
                $pct        = round($percentage, 1);

                [$fillColor, $textColor, $bgColor] = match (true) {
                    $percentage >= 100 => ['#1D9E75', '#ffffff', '#E1F5EE'],
                    $percentage >= 75  => ['#378ADD', '#ffffff', '#E6F1FB'],
                    $percentage >= 50  => ['#EF9F27', '#4A2402', '#FAEEDA'],
                    $percentage >= 25  => ['#E24B4A', '#ffffff', '#FCEBEB'],
                    default            => ['#B4B2A9', '#2C2C2A', '#F1EFE8'],
                };

                $label = match (true) {
                    $percentage >= 100 => 'مكتمل ✓',
                    default            => $pct . '%',
                };

                return '
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="
                                flex:1;
                                height:22px;
                                background:' . $bgColor . ';
                                border-radius:50px;
                                overflow:hidden;
                                border:1px solid ' . $fillColor . '22;
                            ">
                                <div style="
                                    width:' . $percentage . '%;
                                    height:100%;
                                    background:' . $fillColor . ';
                                    border-radius:50px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    min-width: ' . (strlen($label) > 3 ? '60px' : '32px') . ';
                                    transition: width 0.6s ease;
                                ">
                                    <span style="
                                        font-size:11px;
                                        font-weight:700;
                                        color:' . $textColor . ';
                                        white-space:nowrap;
                                        padding:0 6px;
                                    ">' . $label . '</span>
                                </div>
                            </div>
                            <span style="
                                font-size:12px;
                                font-weight:600;
                                color:' . $fillColor . ';
                                min-width:38px;
                                text-align:right;
                            ">' . $pct . '%</span>
                        </div>';
            })

            ->addColumn('status', function ($row) {
                return '<span class="badge ' . $row->status_class . ' px-3 py-2">' . e($row->status_label) . '</span>';
            })

            ->addColumn('created_at_formatted', function ($row) {
                return '<small class="text-muted">' . $row->created_at->locale('ar')->translatedFormat('d F Y') . '</small>';
            })

            ->addColumn('actions', function ($row) {
                $isEnded = $this->isTargetEnded($row);
                return view('tenants.financial_collection.collection_targets.user_target_actions', [
                    'target' => $row,
                    'route' => $this->route,
                    'isEnded' => $isEnded
                ])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return [
            'target_type_badge',
            'target_period',
            'target_amount_display',
            'collected_amount',
            'remaining_amount',
            'progress_bar',
            'status',
            'created_at_formatted',
            'actions'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        return [
            [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => [
                    ['extend' => 'copy', 'text' => 'نسخ'],
                    ['extend' => 'excel', 'text' => 'إكسل', 'title' => 'أهداف المستخدم']
                ],
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Order
    |--------------------------------------------------------------------------
    */
    protected function getCustomOrder(): array
    {
        return [9, 'desc'];
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helper Methods
    |--------------------------------------------------------------------------
    */
    private function isTargetEnded($target): bool
    {
        $endDate = $this->getTargetEndDate($target);
        return Carbon::parse($endDate)->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target End Date
    |--------------------------------------------------------------------------
    */
    private function getTargetEndDate($target): string
    {
        switch ($target->target_type) {
            case 'monthly':
                return Carbon::create($target->target_year, $target->target_month, 1)->endOfMonth()->toDateString();
            case 'yearly':
                return Carbon::create($target->target_year, 12, 31)->endOfYear()->toDateString();
            case 'custom':
                return $target->custom_end_date;
            default:
                return now()->endOfMonth()->toDateString();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target Period Label
    |--------------------------------------------------------------------------
    */
    private function getTargetPeriodLabel($target): string
    {
        switch ($target->target_type) {
            case 'monthly':
                $months = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                return $months[$target->target_month] . ' ' . $target->target_year;
            case 'yearly':
                return 'سنة ' . $target->target_year;
            case 'custom':
                return Carbon::parse($target->custom_start_date)->format('Y-m-d') . ' - ' . Carbon::parse($target->custom_end_date)->format('Y-m-d');
            default:
                return 'غير محدد';
        }
    }
}

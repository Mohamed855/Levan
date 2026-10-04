<?php

namespace App\DataTables\Tenants\FinancialCollection\Target;

use App\DataTables\Abstract\AbstractDataTable;
use App\Services\Tenants\FinancialCollection\Target\CollectionTargetService;
use Carbon\Carbon;

class CollectionTargetsDataTable extends AbstractDataTable
{
    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */
    private $route = "financial-collections.collection-targets";
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
    | Build DataTable
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $request = request();
        if ($request->filled('status')) {
            $query = $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query = $query->where('branch_id', (int) $request->branch_id);
        }
        if ($request->filled('user_id')) {
            $query = $query->where('user_id', (int) $request->user_id);
        }

        $datatable = datatables()->collection($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return "collection-targets-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return $this->collectionTargetService->getUniqueUsersWithLatestTarget();
    }

    /*
    |--------------------------------------------------------------------------
    | Columns Definition
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            [
                'data' => '',
                'orderable' => false,
                'searchable' => false,
                'className' => 'control'
            ],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
            ],
            [
                'data' => 'user_info',
                'name' => 'user_info',
                'title' => 'المستخدم',
                'className' => 'cursor-pointer',
                'orderable' => true,
            ],
            [
                'data' => 'branch_name',
                'name' => 'branch_name',
                'title' => 'الفرع',
                'className' => 'table-ellipsis',
                'width' => '120px'
            ],

            [
                'data' => 'target_type_badge',
                'name' => 'target_type_badge',
                'title' => 'نوع الهدف',
                'className' => 'text-center',
                'orderable' => true,

            ],
            [
                'data' => 'target_period',
                'name' => 'target_period',
                'title' => 'الفترة',
                'className' => 'text-center',
                'orderable' => false,

            ],
            [
                'data' => 'target_amount_display',
                'name' => 'target_amount_display',
                'title' => 'مبلغ الهدف',
                'className' => 'text-center',
                'orderable' => true,

            ],
            [
                'data' => 'progress_text',
                'name' => 'progress_text',
                'title' => 'نسبة الإنجاز',
                'className' => 'text-center',
                'orderable' => false,
            ],
            [
                'data' => 'status',
                'name' => 'status',
                'title' => 'الحالة',
                'className' => 'text-center',
                'width' => '90px',
            ],
            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '90px',
                'className' => 'text-center'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Columns
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '" onclick="event.stopPropagation();">';
            })

            ->addColumn('branch_name', function ($row) {
                return $row->branch
                    ? '<span class="fw-medium">' . e($row->branch->name) . '</span>'
                    : '<span class="badge bg-label-danger">محذوف</span>';
            })

            ->addColumn('user_info', function ($row) {
                if (!$row->user) {
                    return '<span class="badge bg-label-danger">محذوف</span>';
                }


                return $this->routeName(tenant_route_name($this->route . '.user-details', $row->user_id), $row->user->name);
            })

            ->addColumn('target_type_badge', function ($row) {
                $typeClass = match ($row->target_type) {
                    'monthly' => 'bg-label-info',
                    'yearly' => 'bg-label-primary',
                    'custom' => 'bg-label-warning',
                    default => 'bg-secondary'
                };

                return '<div class="clickable-cell" >
                  <span class="badge ' . $typeClass . ' px-3 py-2">' . e($row->target_type_label) . '</span>
                </div>';
            })

            ->addColumn('target_period', function ($row) {
                $period = $this->getTargetPeriodLabel($row);

                return '<div class="clickable-cell" >
                      <span class="fw-medium">' . e($period) . '</span>
                 </div>';
            })

            ->addColumn('target_amount_display', function ($row) {
                $amount = number_format($row->target_amount, 0);

                return '<div class="clickable-cell" >
                <span class="fw-bold">' . $amount . '</span>
                </div>';
            })

            ->addColumn('progress_text', function ($row) {
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
                $checked = $row->status === 'active' ? 'checked' : '';
                $url = tenant_route_name('financial-collections.collection-targets.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_' . $row->id . '" data-url="' . e($url) . '" ' . $checked . ' aria-label="تبديل حالة هدف التحصيل"></div></div>';
            })

            ->addColumn('actions', function ($row) {
                return view('tenants.financial_collection.collection_targets.action', [
                    'id' => $row->id,
                    'userId' => $row->user_id,
                    'route' => $this->route,
                    'collectionTarget' => $row
                ])->render();
            });
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
                $months = [
                    '',
                    'يناير',
                    'فبراير',
                    'مارس',
                    'أبريل',
                    'مايو',
                    'يونيو',
                    'يوليو',
                    'أغسطس',
                    'سبتمبر',
                    'أكتوبر',
                    'نوفمبر',
                    'ديسمبر'
                ];
                return $months[$target->target_month] . ' ' . $target->target_year;

            case 'yearly':
                return 'سنة ' . $target->target_year;

            case 'custom':
                $start = Carbon::parse($target->custom_start_date)->format('Y-m-d');
                $end = Carbon::parse($target->custom_end_date)->format('Y-m-d');
                return $start . ' - ' . $end;

            default:
                return 'غير محدد';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'branch_name',
            'user_info',
            'target_type_badge',
            'target_period',
            'target_amount_display',
            'progress_text',
            'status',
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
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Dropdown Button
    |--------------------------------------------------------------------------
    */
    private function getActionsDropdownButton(): array
    {
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                [
                    'extend' => 'copy',
                    'text' => 'نسخ'
                ],
                [
                    'extend' => 'excel',
                    'text' => 'إكسل',
                    'title' => 'أهداف التحصيل'
                ],
                [
                    'text' => 'حذف المحدد',
                    'className' => 'btn btn-default btn-delete-selected',
                    'action' => "function() { handleMassDelete(); }"
                ]
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Button
    |--------------------------------------------------------------------------
    */
    private function getAddButton(): array
    {
        if (!auth()->user()?->can('collection_targets.create')) {
            return [];
        }

        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة هدف',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}

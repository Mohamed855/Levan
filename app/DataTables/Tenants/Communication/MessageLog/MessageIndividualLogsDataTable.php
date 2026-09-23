<?php

namespace App\DataTables\Tenants\Communication\MessageLog;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Services\Tenants\Communication\MessageLog\MessageIndividualLogService;

class MessageIndividualLogsDataTable extends AbstractDataTable
{
    protected MessageIndividualLogService $messageLogService;
    private string $route = "communication.message-individual-logs";

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initialize the DataTable with required dependencies.
    */
    public function __construct(MessageIndividualLogService $messageLogService)
    {
        $this->messageLogService = $messageLogService;
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable Processing
    |--------------------------------------------------------------------------
    | Process and format the data for display.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        $datatable = $this->applyFilters($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Application
    |--------------------------------------------------------------------------
    | Apply custom filters to the DataTable.
    */
    private function applyFilters($datatable)
    {
        $datatable->filter(function ($query) {
            $globalSearch = request()->input('search.value');

            if (!empty($globalSearch)) {
                $cleanSearch = trim(preg_replace('/\s+/', ' ', $globalSearch));

                $query->where(function ($q) use ($cleanSearch) {
                    $q->where('customer_name', 'like', "%{$cleanSearch}%")
                        ->orWhere('message_content', 'like', "%{$cleanSearch}%")
                        ->orWhere('template_name', 'like', "%{$cleanSearch}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($cleanSearch) {
                            $customerQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('company', function ($companyQuery) use ($cleanSearch) {
                            $companyQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('portfolio', function ($portfolioQuery) use ($cleanSearch) {
                            $portfolioQuery->where('name', 'like', "%{$cleanSearch}%");
                        });
                });
                return;
            }

            if (request()->filled('status')) {
                $query->where('status', request('status'));
            }

            if (request()->filled('message_type')) {
                $query->where('message_type', request('message_type'));
            }

            if (request()->filled('company_id')) {
                $query->where('company_id', request('company_id'));
            }

            if (request()->filled('portfolio_id')) {
                $query->where('portfolio_id', request('portfolio_id'));
            }

            if (request()->filled('customer_id')) {
                $query->where('customer_id', request('customer_id'));
            }

            if (request()->filled('date_from')) {
                $query->whereDate('sent_at', '>=', request('date_from'));
            }

            if (request()->filled('date_to')) {
                $query->whereDate('sent_at', '<=', request('date_to'));
            }
        });

        return $datatable;
    }


    /*
    |--------------------------------------------------------------------------
    | Query Resource
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return $this->messageLogService->getLogsQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | Table Identification
    |--------------------------------------------------------------------------
    | Get the unique table identifier.
    */
    protected function getTableId(): string
    {
        return "message-individual-logs-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Column Definitions
    |--------------------------------------------------------------------------
    | Define the structure and headers of the table columns.
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '', 'className' => 'control'],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => $this->getCheckboxHeader(),
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox'
            ],
            ['data' => 'customer_info', 'name' => 'customer_info', 'title' => 'العميل', 'orderable' => false, 'searchable' => true],
            ['data' => 'identity_number', 'name' => 'identity_number', 'title' => 'رقم الهوية', 'orderable' => false],
            ['data' => 'account_number', 'name' => 'account_number', 'title' => 'رقم الحساب', 'orderable' => false],
            ['data' => 'company_portfolio', 'name' => 'company_portfolio', 'title' => 'الشركة/المحفظة', 'orderable' => false, 'searchable' => true],
            ['data' => 'phone_numbers_count', 'name' => 'phone_numbers_count', 'title' => 'عدد الأرقام', 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'searchable' => false],
            ['data' => 'sent_at_formatted', 'name' => 'sent_at', 'title' => 'تاريخ الإرسال', 'searchable' => false],
            ['data' => 'action', 'name' => 'action', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'className' => 'text-center']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Checkbox Header
    |--------------------------------------------------------------------------
    | Generate simple checkbox header.
    */

    private function getCheckboxHeader(): string
    {
        return '<input type="checkbox" id="select-all">';
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Formatting
    |--------------------------------------------------------------------------
    | Add custom formatting for table columns.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('customer_info', function (MessageIndividualLog $log) {
                $showUrl = tenant_route_name('communication.message-individual-logs.show', $log->id);

                return '<div>' .
                    '<a href="' . $showUrl . '" class="text-decoration-none text-primary" style="font-weight: 500;">' . e($log->customer_name) . '</a>' .
                    '</div>';
            })
            ->addColumn('identity_number', function (MessageIndividualLog $log) {
                return $log->customer?->identity_number ?? '—';
            })

            ->addColumn('account_number', function (MessageIndividualLog $log) {
                return $log->customer?->account_number ?? '—';
            })

            ->addColumn('company_portfolio', function (MessageIndividualLog $log) {
                $companyName = $log->company ? $log->company->name : 'غير محدد';
                $portfolioName = $log->portfolio ? $log->portfolio->name : 'غير محدد';

                return '<div>' .
                    '<div class="small">' . e($companyName) . '</div>' .
                    '<div class="small text-muted">' . e($portfolioName) . '</div>' .
                    '</div>';
            })
            ->editColumn('phone_numbers_count', function (MessageIndividualLog $log) {
                return '<span class="badge bg-label-primary">' . $log->phone_numbers_count . '</span>';
            })
            ->addColumn('status', function (MessageIndividualLog $log) {
                $statusConfig = [
                    'pending' => ['class' => 'bg-label-warning', 'text' => 'معلق'],
                    'processing' => ['class' => 'bg-label-info', 'text' => 'قيد المعالجة'],
                    'sent' => ['class' => 'bg-label-success', 'text' => 'تم الإرسال'],
                    'failed' => ['class' => 'bg-label-danger', 'text' => 'فشل'],
                    'cancelled' => ['class' => 'bg-label-secondary', 'text' => 'ملغي']
                ];

                $config = $statusConfig[$log->status] ?? ['class' => 'bg-label-secondary', 'text' => $log->status];

                return '<span class="badge ' . $config['class'] . '">' . $config['text'] . '</span>';
            })
            ->editColumn('total_cost', function (MessageIndividualLog $log) {
                return '<span class="fw-medium">' . number_format($log->total_cost, 2) . ' ريال</span>';
            })
            ->editColumn('sent_at_formatted', function (MessageIndividualLog $log) {
                if ($log->sent_at) {
                    return $log->sent_at->format('Y-m-d H:i');
                }
                return '<span class="text-muted">لم يتم الإرسال</span>';
            })
            ->addColumn('action', function ($row) {
                return view('tenants.communication.message_individual_logs.partials.action', ['id' => $row->id, 'route' => $this->route])->render();
            })
            ->orderColumn('phone_numbers_count', fn($query, $order) => $query->orderBy('phone_numbers_count', $order))
            ->orderColumn('total_cost', fn($query, $order) => $query->orderBy('total_cost', $order))
            ->orderColumn('sent_at', fn($query, $order) => $query->orderBy('sent_at', $order))
            ->filterColumn('customer_info', function ($query, $keyword) {
                $query->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
            })
            ->filterColumn('company_portfolio', function ($query, $keyword) {
                $query->whereHas('company', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                })->orWhereHas('portfolio', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    | Specify columns that contain HTML content.
    */
    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'customer_info',
            'company_portfolio',
            'phone_numbers_count',
            'status',
            'total_cost',
            'sent_at_formatted',
            'identity_number',
            'account_number',
            'action'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable Buttons
    |--------------------------------------------------------------------------
    | Define action buttons for the DataTable.
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Get Actions Dropdown Button
    |--------------------------------------------------------------------------
    | Builds the "Actions" dropdown button and defines all the items within it.
    */
    private function getActionsDropdownButton(): array
    {
        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => [
                [
                    'extend' => 'copy',
                    'text'   => 'نسخ'
                ],
                [
                    'text'      => 'تصدير اكسل',
                    'className' => 'btn btn-default btn-export-excel',
                    'action'    => "function() { handleExport(); }",
                ],
                [
                    'text'      => 'عرض الأرشيف',
                    'className' => 'btn btn-default btn-view-archives',
                    'action'    => "function() { window.location.href = window.routes.archives; }",
                ],
                [
                    'text'      => 'حذف المحدد',
                    'className' => 'btn btn-default btn-delete-selected',
                    'action'    => "function() { handleMassDelete(); }",
                ]
            ],
        ];
    }
}

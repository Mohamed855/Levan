<?php

namespace App\DataTables\Tenants\FinancialCollection\Admin;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminCollectionsDataTable extends AbstractDataTable
{
    private $route = "financial-collections.admin-collections";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    | Build the DataTable response and mark raw columns.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();

        $datatable->filterColumn('customer_info', function ($query, $keyword) {
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('account_number', 'like', "%{$keyword}%");
            });
        });

        $datatable->filterColumn('account_number', function ($query, $keyword) {
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('account_number', 'like', "%{$keyword}%");
            });
        });

        $datatable->filterColumn('processor_info', function ($query, $keyword) {
            $query->whereHas('approvedBy', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            });
        });

        $datatable->filterColumn('added_by', function ($query, $keyword) {
            $query->whereHas('addedBy', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            });
        });

        $datatable->filterColumn('receipt_number', function ($query, $keyword) {
            $query->where('receipt_number', 'like', "%{$keyword}%");
        });

        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }


    /*
    |--------------------------------------------------------------------------
    | Table Id
    |--------------------------------------------------------------------------
    | Provide the unique HTML id for the table element.
    */
    protected function getTableId()
    {
        return "admin-collections-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return Collection::select([
            'id',
            'customer_id',
            'collected_amount',
            'collection_type',
            'payment_method_id',
            'receipt_number',
            'closing_code',
            'collection_date',
            'payment_type',
            'attachment',
            'status',
            'added_by',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'created_at'
        ])->with([
            'customer:id,name,phone,account_number',
            'paymentMethod:id,name',
            'addedBy:id,name',
            'approvedBy:id,name'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    | Define the table columns structure.
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
            ['data' => 'id', 'visible' => false],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => $this->getCheckboxHeader(),
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'createdCell' => "function(td) { $(td).css('padding-left', '38px'); }",
                'defaultContent' => ''
            ],
            [
                'data' => 'customer_info',
                'name' => 'customer_info',
                'title' => 'العميل',
                'className' => 'table-ellipsis',
                'searchable' => true
            ],
            [
                'data' => 'account_number',
                'name' => 'account_number',
                'title' => 'رقم الحساب',
                'className' => 'table-ellipsis',
                'searchable' => true,
                'orderable' => true
            ],
            [
                'data' => 'amount_info',
                'name' => 'amount_info',
                'title' => 'المبلغ',
                'className' => 'table-ellipsis'
            ],
            [
                'data' => 'collection_details',
                'name' => 'collection_details',
                'title' => 'تفاصيل التحصيل',
                'orderable' => false,
                'searchable' => false
            ],
            [
                'data' => 'collection_date_formatted',
                'name' => 'collection_date',
                'title' => 'تاريخ التحصيل'
            ],
            [
                'data' => 'status',
                'name' => 'status',
                'title' => 'الحالة'
            ],
            [
                'data' => 'processor_info',
                'name' => 'processor_info',
                'title' => 'معالجة الطلب',
                'orderable' => false,
                'searchable' => false,
                'className' => 'table-ellipsis'
            ],
            [
                'data' => 'added_by',
                'name' => 'added_by',
                'title' => 'أُضيف بواسطة'
            ],
            [
                'data' => 'created_at_formatted',
                'name' => 'created_at',
                'title' => 'تاريخ الإضافة'
            ],
            [
                'data' => 'attachment',
                'name' => 'attachment',
                'title' => 'المرفق',
                'orderable' => false,
                'searchable' => false
            ],
            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
                'className' => 'text-center'
            ]
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Custom Columns
    |--------------------------------------------------------------------------
    | Add/format custom columns.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('customer_info', function (Collection $collection) {
                if (!$collection->customer || !$collection->customer->name) {
                    return '<span class="text-muted">عميل غير محدد</span>';
                }

                $customerName = e($collection->customer->name);
                $customerUrl = tenant_route_name('customers.show', $collection->customer->id);

                return '<a href="' . $customerUrl . '" class="text-primary text-decoration-none fw-medium">' .
                    $customerName .
                    '</a>';
            })
            ->addColumn('account_number', function (Collection $collection) {
                return '<span class="text-muted">' .
                    ($collection->customer->account_number ?? 'غير محدد') .
                    '</span>';
            })
            ->addColumn('amount_info', function (Collection $collection) {
                return '<div class="fw-medium text-primary">' . $collection->formatted_collected_amount . '</div>';
            })
            ->addColumn('collection_details', function (Collection $collection) {
                $typeClass = $collection->collection_type === 'cash' ? 'bg-label-primary' : 'bg-label-info';
                $paymentTypeClass = $collection->payment_type === 'settlement' ? 'bg-label-primary' : 'bg-secondary';

                return '<div>' .
                    '<span class="badge ' . $typeClass . '">' . $collection->collection_type_label . '</span> ' .
                    '<span class="badge ' . $paymentTypeClass . '">' . $collection->payment_type_label . '</span> ' .
                    '</div>';
            })

            ->addColumn('collection_date_formatted', function (Collection $collection) {
                return '<span class="text-primary">' . $collection->formatted_collection_date . '</span>';
            })
            ->addColumn('status', function (Collection $collection) {
                $statusBadge = '<span class="badge ' . $collection->status_class . '">' . $collection->status_label . '</span>';

                if ($collection->status === 'rejected' && $collection->rejection_reason) {
                    $statusBadge .= ' <small class="text-muted" title="' .
                        e($collection->rejection_reason) . '">
                                       <i class="ti ti-info-circle"></i>
                                   </small>';
                }
                return $statusBadge;
            })
            ->addColumn('processor_info', function (Collection $collection) {
                if ($collection->status === 'pending') {
                    return '<span class="text-muted">في انتظار المعالجة</span>';
                }
                if (!$collection->approvedBy || !$collection->approvedBy->name) {
                    return '<span class="text-muted">غير محدد</span>';
                }
                return '<span class="fw-medium text-primary">' . e($collection->approvedBy->name) . '</span>';
            })
            ->addColumn('added_by', function (Collection $collection) {
                if (!$collection->addedBy || !$collection->addedBy->name) {
                    return '<span class="text-muted">غير محدد</span>';
                }

                return '<span class="text-primary">' . e($collection->addedBy->name) . '</span>';
            })

            ->addColumn('created_at_formatted', function (Collection $collection) {
                return $collection->created_at->locale('ar')->translatedFormat('d F Y');
            })
            ->addColumn('attachment', function (Collection $collection) {
                if ($collection->attachment) {
                    $url = tasset($collection->attachment);
                    return '<a href="' . $url . '" target="_blank" class="btn btn-icon btn-label-primary" title="عرض المرفق">
                                <i class="ti ti-paperclip"></i>
                            </a>';
                }
                return '<span class="text-muted">لا يوجد</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.financial_collection.admin_collections.action', [
                    'id' => $row->id,
                    'route' => $this->route,
                    'collection' => $row
                ])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    | Specify columns that contain raw HTML.
    */
    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'customer_info',
            'account_number',
            'amount_info',
            'collection_details',
            'collection_date_formatted',
            'status',
            'processor_info',
            'added_by',
            'attachment',
            'actions'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    | Apply custom filters coming from the UI.
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('collection_type')) {
            $query->where('collection_type', $request->collection_type);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('added_by')) {
            $query->where('added_by', $request->added_by);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('collection_date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('collection_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('collection_date', '<=', $request->date_to);
        }

        return $query;
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    | Define DataTable top buttons.
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Dropdown Button
    |--------------------------------------------------------------------------
    | Build the 'Actions' dropdown content.
    */
    private function getActionsDropdownButton(): array
    {
        $buttons = [
            [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => []
            ]
        ];

        $buttons[0]['buttons'][] = [
            'extend' => 'copy',
            'text' => 'نسخ'
        ];

        $buttons[0]['buttons'][] = [
            'extend' => 'excel',
            'text' => 'إكسل',
            'title' => 'تحصيلات المستخدمين'
        ];

        // if (Auth::user()->can('استيراد التحصيلات')) {
        $buttons[0]['buttons'][] = [
            'text' => 'استيراد التحصيلات',
            'action' => "function() { window.location.href = window.routes.collectionsImport; }"
        ];
        // }

        if (Auth::user()->can('admin_collections.approve_all')) {
            $buttons[0]['buttons'][] = [
                'text' => 'اعتماد المحدد',
                'action' => "function() { handleMassApprove(); }"
            ];
        }

        if (Auth::user()->can('admin_collections.reject_all')) {
            $buttons[0]['buttons'][] = [
                'text' => 'رفض المحدد',
                'action' => "function() { handleMassReject(); }"
            ];
        }

        if (Auth::user()->can('admin_collections.delete_selected')) {
            $buttons[0]['buttons'][] = [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => "function() { handleMassDelete(); }"
            ];
        }

        return $buttons;
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Checkbox Header
    |--------------------------------------------------------------------------
    */
    private function getCheckboxHeader(): string
    {
        return '<div class="d-flex justify-content-center h-100 w-100" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" tabindex="0" id="selectAllDropdownCell">
            <input type="checkbox" id="select-all" disabled style="pointer-events: none;">
             <i class="ti ti-chevron-down ms-3"></i>
              </div>
              <ul class="dropdown-menu p-2" aria-labelledby="selectAllDropdownCell" style="min-width: 180px;">
              <li>
                  <a href="#" class="dropdown-item d-flex align-items-center" id="select-page">
                      <input type="checkbox" class="row-checkbox me-2" id="selectPageCheckbox" disabled>
                      <span>الصفحة</span>
                  </a>
              </li>
              <li>
                  <a href="#" class="dropdown-item d-flex align-items-center" id="select-all-items">
                      <input type="checkbox" class="row-checkbox me-2" id="selectAllCheckbox" disabled>
                      <span>الكل</span>
                  </a>
              </li>
          </ul>';
    }
}

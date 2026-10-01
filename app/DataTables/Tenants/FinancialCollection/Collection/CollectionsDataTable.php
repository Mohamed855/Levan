<?php

namespace App\DataTables\Tenants\FinancialCollection\Collection;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Carbon\Carbon;

class CollectionsDataTable extends AbstractDataTable
{
    private $route = "financial-collections.collections";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();


        $datatable->filterColumn('customer_info', function ($query, $keyword) {
            $query->whereHas('customerForTable', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        });

        $datatable->filterColumn('account_number', function ($query, $keyword) {
            $query->whereHas('customerForTable', function ($q) use ($keyword) {
                $q->where('account_number', 'like', "%{$keyword}%");
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
    */
    protected function getTableId()
    {
        return "collections-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return Collection::query()
            ->select([
                'collections.id',
                'collections.customer_id',
                'collections.collected_amount',
                'collections.collection_type',
                'collections.payment_method_id',
                'collections.receipt_number',
                'collections.closing_code',
                'collections.collection_date',
                'collections.payment_type',
                'collections.attachment',
                'collections.status',
                'collections.added_by',
                'collections.approved_by',
                'collections.approved_at',
                'collections.rejection_reason',
                'collections.created_at'
            ])
            ->with([
                'customerForTable:id,name,phone,account_number,relationship_manager_id',
                'paymentMethod:id,name',
                'addedBy:id,name',
                'approvedBy:id,name'
            ])
            ->where('added_by', auth()->id());
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
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
                'titleAttr' => 'تحديد الكل',
            ],
            [
                'data' => 'customer_info',
                'name' => 'customer_info',
                'title' => 'العميل',
                'className' => 'table-ellipsis'
            ],
            [
                'data' => 'account_number',
                'name' => 'account_number',
                'title' => 'رقم الحساب',
                'className' => 'table-ellipsis',
                'searchable' => true
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
            // [
            //     'data' => 'added_by',
            //     'name' => 'added_by',
            //     'title' => 'أُضيف بواسطة'
            // ],
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
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('customer_info', function (Collection $collection) {
                $customer = $collection->customerForTable;

                if (!$customer) {
                    return '<span class="text-danger">عميل غير موجود</span>';
                }

                $canOpenCustomer =
                    auth()->user()?->can('customers.view') ||
                    ($customer->relationship_manager_id === auth()->id());

                if ($canOpenCustomer) {
                    $customerUrl = tenant_route_name('customers.show', $customer->id);

                    return '<a href="' . $customerUrl . '" class="text-primary text-decoration-none fw-medium">'
                        . e($customer->name)
                        . '</a>';
                }

                // عرض الاسم فقط بدون رابط
                return '<span class="fw-medium text-dark">' . e($customer->name) . '</span>';
            })

            ->addColumn('account_number', function (Collection $collection) {
                return '<span class="text-muted">'
                    . e(optional($collection->customerForTable)->account_number ?? 'غير محدد')
                    . '</span>';
            })
            ->addColumn('amount_info', function (Collection $collection) {
                return '<div class="fw-medium text-primary">' . $collection->formatted_collected_amount . '</div>';
            })
            ->addColumn('collection_details', function (Collection $collection) {
                $typeClass = $collection->collection_type === 'cash' ? 'bg-label-primary' : 'bg-label-info';
                $paymentTypeClass = $collection->payment_type === 'settlement' ? 'bg-label-primary' : 'bg-label-secondary';
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
            // ->addColumn('added_by', function (Collection $collection) {
            //     return $collection->addedBy ?
            //         '<span class="text-primary">' . e($collection->addedBy->name) . '</span>' :
            //         'غير محدد';
            // })
            ->addColumn('created_at_formatted', function (Collection $collection) {
                return $collection->created_at->locale('ar')->translatedFormat('d F Y');
            })
            ->addColumn('attachment', function (Collection $collection) {
                if ($collection->attachment) {
                    $url = tasset($collection->attachment);
                    return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-icon btn-text-primary" title="عرض المرفق">
                                <i class="ti ti-paperclip"></i>
                            </a>';
                }
                return '<span class="text-muted">لا يوجد</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.financial_collection.collections.action', [
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
            'added_by',
            'attachment',
            'actions'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
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
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('collection_date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('collection_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('collection_date', '<=', $request->date_to);
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
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
        $actions = [];
        $actions[] = [
            'extend' => 'copy',
            'text' => 'نسخ'
        ];
        $actions[] = [
            'extend' => 'excel',
            'text' => 'إكسل',
            'title' => 'التحصيلات المالية'
        ];

        if (auth()->user()?->can('collections.delete')) {
            $actions[] = [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => "function() { handleMassDelete(); }"
            ];
        }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $actions,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Button
    |--------------------------------------------------------------------------
    */
    private function getAddButton(): array
    {
        if (!auth()->user()?->can('collections.create')) {
            return [];
        }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة تحصيل',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}

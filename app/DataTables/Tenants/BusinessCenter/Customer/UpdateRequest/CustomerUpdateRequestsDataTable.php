<?php

namespace App\DataTables\Tenants\BusinessCenter\Customer\UpdateRequest;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;

class CustomerUpdateRequestsDataTable extends AbstractDataTable
{
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    private $route = 'customers.update-requests';

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
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
        return 'customer-update-requests-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return CustomerUpdateRequest::with([
            'customer:id,name,identity_number',
            'requestedBy:id,name',
            'approvedBy:id,name',
        ])->select('customer_update_requests.*');
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
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all" class="form-check-input"></span>',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox text-center',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'id', 'name' => 'id', 'title' => 'رقم الطلب'],
            ['data' => 'customer.name', 'name' => 'customer.name', 'title' => 'اسم العميل'],
            ['data' => 'customer.identity_number', 'name' => 'customer.identity_number', 'title' => 'رقم الهوية'],
            ['data' => 'requestedBy.name', 'name' => 'requestedBy.name', 'title' => 'مقدم الطلب'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الطلب'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'approvedBy.name', 'name' => 'approvedBy.name', 'title' => 'معالج الطلب'],
            ['data' => 'approved_at', 'name' => 'approved_at', 'title' => 'تاريخ المعالجة'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Custom Columns
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('customer.name', function ($row) {
                return '<a href="'.tenant_route_name('customers.show', $row->customer_id).'" class="text-primary text-decoration-none d-inline-flex align-items-center">'.
                    '<span class="fw-bold">'.($row->customer?->name ?? 'غير محدد').'</span>'.
                    '</a>';
            })
            ->editColumn('customer.identity_number', function ($row) {
                return '<span class="fw-bold text-dark">'.($row->customer?->identity_number ?? 'غير محدد').'</span>';
            })
            ->editColumn('requestedBy.name', function ($row) {
                return '<span class="fw-bold text-dark">'.($row->requestedBy?->name ?? 'غير محدد').'</span>';
            })
            ->editColumn('created_at', function ($row) {
                return '<span class="text-muted">'.$row->created_at->format('Y-m-d H:i').'</span>';
            })
            ->editColumn('status', function ($row) {
                return $row->status_label;
            })
            ->editColumn('approvedBy.name', function ($row) {
                return $row->approvedBy ?
                    '<span class="fw-bold text-dark">'.$row->approvedBy->name.'</span>' :
                    '<span class="text-muted">لم تتم المعالجة</span>';
            })
            ->editColumn('approved_at', function ($row) {
                return $row->approved_at ?
                    '<span class="text-muted">'.$row->approved_at->format('Y-m-d H:i').'</span>' :
                    '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.business_center.customers.update-requests.action', ['row' => $row])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'customer.name', 'customer.identity_number', 'requestedBy.name', 'approvedBy.name', 'approved_at', 'created_at', 'status', 'actions'];
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
            $query->where('customer_update_requests.status', $request->status);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('identity_number', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('requestedBy', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Define DataTable Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Actions Dropdown Button
    |--------------------------------------------------------------------------
    */
    protected function getActionsDropdownButton(): array
    {
        $buttons = [
            [
                'extend' => 'copy',
                'text' => 'نسخ',
                'className' => 'dropdown-item',
            ],
            [
                'text' => 'تصدير اكسل',
                'className' => 'dropdown-item',
                'action' => 'function() { exportUpdateRequests(); }',
            ],
        ];
        if (auth()->user()?->can('customer_update_requests.approve')) {
            $buttons[] = [
                'text' => 'موافقة جماعية',
                'className' => 'dropdown-item',
                'action' => 'function() { approveSelectedRequests(); }',
            ];
        }
        if (auth()->user()?->can('customer_update_requests.reject')) {
            $buttons[] = [
                'text' => 'رفض جماعي',
                'className' => 'dropdown-item',
                'action' => 'function() { rejectSelectedRequests(); }',
            ];
        }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export dropdown-toggle me-2',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}

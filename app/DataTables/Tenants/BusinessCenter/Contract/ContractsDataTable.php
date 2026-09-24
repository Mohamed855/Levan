<?php

namespace App\DataTables\Tenants\BusinessCenter\Contract;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Contract\Contract;

class ContractsDataTable extends AbstractDataTable
{
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    private $route = "contracts";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
    */
    public function dataTable($query)
    {
        if ($query instanceof \Illuminate\Support\Collection) {
            return datatables()->collection($query)->addIndexColumn();
        }

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
        return "contracts-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('contracts')) {
            return collect([]);
        }

        return Contract::with('company:id,name')->select('contracts.*');
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
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all" class="form-check-input"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width'      => '10px',
                'className'  => 'custom-checkbox text-center',
                'titleAttr'  => 'تحديد الكل',
            ],
            ['data' => 'contract_number', 'name' => 'contract_number', 'title' => 'رقم العقد'],
            ['data' => 'company.name', 'name' => 'company.name', 'title' => 'اسم الشركة'],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => 'تاريخ البداية'],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => 'تاريخ النهاية'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
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
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('contract_number', function ($row) {
                return '<a href="' . tenant_route_name('contracts.show', $row->id) . '" class="text-primary text-decoration-none d-inline-flex align-items-center">' .
                    '<span class="fw-bold">' . $row->contract_number . '</span>' .
                    '</a>';
            })
            ->editColumn('company.name', function ($row) {
                return '<span class="fw-bold text-dark">' . ($row->company->name ?? 'غير محدد') . '</span>';
            })
            ->editColumn('start_date', function ($row) {
                return '<span class="text-muted">' . $row->formatted_start_date . '</span>';
            })
            ->editColumn('end_date', function ($row) {
                return '<span class="text-muted">' . $row->formatted_end_date . '</span>';
            })
            ->editColumn('status', function ($row) {
                $canEdit = auth()->user()?->can('تعديل عقد');

                if ($canEdit) {
                    return '<span class="badge ' . $row->status_class . ' cursor-pointer js-toggle-btn"
                      data-id="' . $row->id . '"
                      data-url="' . tenant_route_name('contracts.toggle-status', $row->id) . '"
                      title="اضغط لتبديل الحالة">
                    ' . $row->status_label . '
                   </span>';
                } else {
                    return '<span class="badge ' . $row->status_class . '" 
                      title="للقراءة فقط">
                    ' . $row->status_label . '
                </span>';
                }
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.business_center.contracts.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'contract_number', 'company.name', 'start_date', 'end_date', 'status', 'actions'];
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
            $query->where('contracts.status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contract_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                        $companyQuery->where('name', 'like', "%{$searchTerm}%");
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
            $this->getActionsDropdownButton('mass-delete-contracts'),
            $this->getAddButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Actions Dropdown Button
    |--------------------------------------------------------------------------
    */
    protected function getActionsDropdownButton(string $massDeleteRouteName = 'mass-delete'): array
    {
        $buttons = [
            [
                'extend' => 'excel',
                'text'   => 'إكسل',
                'className' => 'dropdown-item'
            ],
            [
                'extend' => 'copy',
                'text'   => 'نسخ',
                'className' => 'dropdown-item'
            ],
        ];

        if (auth()->user()?->can('حذف عقد')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'dropdown-item',
                'action'    => "function() { handleMassDelete(); }",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export dropdown-toggle me-2',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Add Button
    |--------------------------------------------------------------------------
    */
    private function getAddButton(): array
    {
        if (!auth()->user()?->can('إضافة عقد')) {
            return [];
        }

        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة عقد',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}

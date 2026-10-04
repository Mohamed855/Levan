<?php

namespace App\DataTables\Tenants\Branch;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Branch\Branch;

class BranchDataTable extends AbstractDataTable
{
    private $route = "branches";

    /*
    |--------------------------------------------------------------------------
    | Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId()
    {
        return "branches-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return Branch::query()
            ->select(['id', 'name', 'code', 'city', 'phone', 'status', 'created_at'])
            ->with('creator:id,name');
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
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
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width'      => '10px',
                'className'  => 'custom-checkbox',
                'titleAttr'  => 'تحديد الكل',
            ],
            ['data' => 'name',   'name' => 'name',   'title' => 'اسم الفرع',  'className' => 'text-start', 'width' => '200px'],
            ['data' => 'code',   'name' => 'code',   'title' => 'الكود',      'className' => 'text-start', 'width' => '100px'],
            ['data' => 'city',   'name' => 'city',   'title' => 'المدينة',    'className' => 'text-start', 'width' => '120px'],
            ['data' => 'phone',  'name' => 'phone',  'title' => 'الهاتف',     'className' => 'text-start', 'width' => '120px'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة',     'width' => '100px', 'className' => 'text-center'],
            ['data' => 'actions','name' => 'actions','title' => 'الإجراءات',  'orderable' => false, 'searchable' => false, 'width' => '120px'],
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

            ->editColumn('code', function ($row) {
                return '<code class="badge bg-secondary">' . e($row->code) . '</code>';
            })

            ->editColumn('city', function ($row) {
                return $row->city 
                    ? '<span class="text-body">' . e($row->city) . '</span>' 
                    : '<span class="text-muted">-</span>';
            })

            ->editColumn('phone', function ($row) {
                return $row->phone 
                    ? '<span class="text-body">' . e($row->phone) . '</span>' 
                    : '<span class="text-muted">-</span>';
            })

            ->editColumn('status', function ($row) {
                $checked = $row->status === 'active' ? 'checked' : '';
                $url = tenant_route_name('branches.toggle-status', ['branch' => $row->id]);
                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input js-table-status-toggle"
                                id="status_' . $row->id . '"
                                data-url="' . e($url) . '"
                                ' . $checked . '
                                aria-label="تبديل حالة الفرع"
                            >
                        </div>
                    </div>
                ';
            })

            ->addColumn('actions', function ($row) {
                return view('tenants.branches.action', [
                    'row'   => $row,
                    'id'    => $row->id,
                    'route' => $this->route
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
        return ['checkbox', 'code', 'city', 'phone', 'status', 'actions'];
    }

    /*
    |--------------------------------------------------------------------------
    | Searchable Columns
    |--------------------------------------------------------------------------
    */
    protected function getSearchableColumns(): array
    {
        return ['name', 'code', 'city', 'phone'];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Order
    |--------------------------------------------------------------------------
    */
    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
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

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
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
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        // if (auth()->user()?->can('حذف فرع')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); }",
            ];
        // }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Button
    |--------------------------------------------------------------------------
    */
    private function getAddButton(): array
    {
        // if (!auth()->user()?->can('إضافة فرع')) {
        //     return [];
        // }

        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة فرع',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}

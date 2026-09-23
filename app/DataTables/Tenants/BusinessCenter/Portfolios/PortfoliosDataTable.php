<?php

namespace App\DataTables\Tenants\BusinessCenter\Portfolios;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PortfoliosDataTable extends AbstractDataTable
{
    private $route = "portfolios";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response (Classic & Compatible Method)
    |--------------------------------------------------------------------------
    | This structure is compatible with all versions of Yajra DataTables and
    | has been proven to work correctly in your CompaniesDataTable.
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
        return "portfolios-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource (FIXED - Following Companies' Pattern)
    |--------------------------------------------------------------------------
    | We use `with()` for efficient data loading for display purposes.
    | This is the main fix for the 500 server error.
    */
    protected function resource(): Builder
    {
        return Portfolio::query()->with(['company:id,name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    | Defines the structure for display. Related columns are made not sortable
    | because `with()` doesn't allow direct sorting on related tables.
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'company_name', 'name' => 'company.name', 'title' => 'اسم الشركة', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المحفظة'],
            ['data' => 'assignment_date', 'name' => 'assignment_date', 'title' => 'تاريخ الإسناد'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],
            ['data' => 'action', 'name' => 'action', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Modifications
    |--------------------------------------------------------------------------
    | This is where we handle the display logic for data from relationships.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('company_name', function (Portfolio $portfolio) {
                $companyName = $portfolio->company->name ?? 'غير محدد';
                return '<span class="text-primary">' . e($companyName) . '</span>';
            })
            ->editColumn('name', function (Portfolio $portfolio) {
                $url = tenant_route_name('portfolios.show', $portfolio->id);
                return '<a href="' . $url . '" class="text-body fw-medium">' . e($portfolio->name) . '</a>';
            })
            ->editColumn('assignment_date', fn($row) => $row->assignment_date ? Carbon::parse($row->assignment_date)->format('Y-m-d') : '<span class="text-muted">لم يتم الإسناد	</span>')
            ->editColumn('status', function (Portfolio $portfolio) {
                $canEdit = auth()->user()?->can('تعديل محفظة');

                if ($canEdit) {
                    return '<span class="badge ' . $portfolio->status_class . ' cursor-pointer js-toggle-btn"
                      data-id="' . $portfolio->id . '"
                      data-url="' . tenant_route_name('portfolios.toggle-status', $portfolio->id) . '"
                      title="اضغط لتبديل الحالة">
                    ' . $portfolio->status_label . '
                </span>';
                } else {
                    return '<span class="badge ' . $portfolio->status_class . '" 
                      title="للقراءة فقط">
                    ' . $portfolio->status_label . '
                </span>';
                }
            })
            ->editColumn('created_at', fn($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->addColumn('action', fn($row) => view('tenants.business_center.portfolios.action', ['id' => $row->id, 'route' => $this->route])->render());
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'company_name', 'name', 'assignment_date', 'status', 'action'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    | Uses `whereHas` for filtering on related tables, which is the correct
    | approach when using `with()`.
    */
    protected function applyCustomFilters($query)
    {
        $request = request();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Define All DataTable Buttons
    |--------------------------------------------------------------------------
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('حذف محفظة')) {
            $buttons[] = [
                'text' => 'حذف المحدد',
                'action' => "function() { handleMassDelete(); }"
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export btn',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }


    private function getAddButton(): array
    {
        if (!auth()->user()?->can('إضافة محفظة')) {
            return [];
        }

        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة محفظة',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}

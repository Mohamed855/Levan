<?php

namespace App\DataTables\Tenants\Archive\Portfolio;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Carbon\Carbon;

class ArchivedPortfoliosDataTable extends AbstractDataTable
{
    private $route = "archive.portfolios";

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    | Build the DataTable response and mark raw columns.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();

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
        return "archived-portfolios-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return Portfolio::onlyTrashed()
            ->select([
                'portfolios.id',
                'portfolios.name',
                'portfolios.company_id',
                'portfolios.assignment_date',
                'portfolios.status',
                'portfolios.deleted_at',
                'portfolios.created_at',
                'companies.name as company_name'
            ])
            ->leftJoin('companies', 'portfolios.company_id', '=', 'companies.id');
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
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'portfolios.id', 'title' => 'ID', 'visible' => false],
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
            ['data' => 'name', 'name' => 'portfolios.name', 'title' => 'اسم المحفظة', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'company_name', 'name' => 'companies.name', 'title' => 'الشركة', 'width' => '150px'],
            ['data' => 'assignment_date', 'name' => 'portfolios.assignment_date', 'title' => 'تاريخ التعيين', 'width' => '120px'],
            ['data' => 'status', 'name' => 'portfolios.status', 'title' => 'الحالة', 'width' => '100px'],
            ['data' => 'deleted_at', 'name' => 'portfolios.deleted_at', 'title' => 'تاريخ الأرشفة', 'width' => '120px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
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
            ->editColumn('name', function ($row) {
                return '<span class="text-body fw-medium">' . $row->name . '</span>';
            })
            ->editColumn('company_name', function ($row) {
                return '<span class="text-primary">' . ($row->company_name ?? 'غير محدد') . '</span>';
            })
            ->editColumn('assignment_date', function ($row) {
                return $row->assignment_date ? Carbon::parse($row->assignment_date)->format('Y-m-d') : 'غير محدد';
            })
            ->editColumn('status', function ($row) {
                $statusClass = $row->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $row->status === 'active' ? 'نشط' : 'غير نشط';
                return '<span class="badge ' . $statusClass . '">' . $statusText . '</span>';
            })
            ->editColumn('deleted_at', function ($row) {
                return Carbon::parse($row->deleted_at)->format('Y-m-d');
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.archive.portfolios.action', ['id' => $row->id, 'route' => $this->route])->render();
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
        return ['checkbox', 'name', 'company_name', 'status', 'actions'];
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
            $query->where('portfolios.status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('portfolios.company_id', $request->company_id);
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where('portfolios.name', 'like', "%{$searchTerm}%");
        }

        return $query;
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
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('portfolios_archive.restore')) {
            $buttons[] = [
                'text'      => 'استرجاع المحدد',
                'className' => 'btn btn-default btn-restore-selected',
                'action'    => "function() { handleMassRestore(); }",
            ];
        }

        if (auth()->user()?->can('portfolios_archive.force_delete')) {
            $buttons[] = [
                'text'      => 'حذف نهائي للمحدد',
                'className' => 'btn btn-default btn-force-delete-selected',
                'action'    => "function() { handleMassForceDelete(); }",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }
}

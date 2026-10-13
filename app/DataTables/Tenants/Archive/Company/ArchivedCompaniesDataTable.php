<?php

namespace App\DataTables\Tenants\Archive\Company;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Company\Company;
use Carbon\Carbon;

class ArchivedCompaniesDataTable extends AbstractDataTable
{
    private $route = 'archive.companies';

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    | Build the DataTable response and mark raw columns.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();

        $datatable->filterColumn('authorized_people_count', function ($query, $keyword) {
            if (is_numeric($keyword)) {
                $query->having('authorized_people_count', '=', (int) $keyword);
            } else {
                $query->havingRaw('CAST(authorized_people_count AS CHAR) LIKE ?', ["%{$keyword}%"]);
            }
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
        return 'archived-companies-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return Company::onlyTrashed()
            ->select(['id', 'name', 'status', 'logo_path', 'deleted_at', 'created_at'])
            ->withCount('authorizedPeople');
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
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
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
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الشركة', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'authorized_people_count', 'name' => 'authorized_people_count', 'title' => 'عدد المفوضين', 'width' => '100px'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '100px'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الأرشفة', 'width' => '120px'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء', 'width' => '120px'],
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
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('name', function ($row) {
                $img = $row->logo_path
                    ? tasset($row->logo_path)
                    : asset('static/brand/logos/logo.svg');

                return '<div class="d-flex align-items-center">
                    <img src="'.$img.'" alt="logo" class="rounded me-2"
                         style="width:28px;height:28px;object-fit:cover" />
                    <span class="text-body fw-medium">'.$row->name.'</span>
                </div>';
            })
            ->editColumn('authorized_people_count', function ($row) {
                return '<span class="badge bg-label-info">'.($row->authorized_people_count ?? 0).'</span>';
            })
            ->editColumn('status', function ($row) {
                $statusClass = $row->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $row->status === 'active' ? 'نشط' : 'غير نشط';

                return '<span class="badge '.$statusClass.'">'.$statusText.'</span>';
            })
            ->editColumn('deleted_at', function ($row) {
                return Carbon::parse($row->deleted_at)->format('Y-m-d');
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d');
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.archive.companies.action', ['id' => $row->id, 'route' => $this->route])->render();
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
        return ['checkbox', 'name', 'authorized_people_count', 'status', 'actions'];
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

        if ($request->filled('authorized_count')) {
            switch ($request->authorized_count) {
                case '0':
                    $query->has('authorizedPeople', '=', 0);
                    break;
                case '1':
                    $query->has('authorizedPeople', '=', 1);
                    break;
                case '2-5':
                    $query->has('authorizedPeople', '>=', 2)
                        ->has('authorizedPeople', '<=', 5);
                    break;
                case '6+':
                    $query->has('authorizedPeople', '>=', 6);
                    break;
            }
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('authorizedPeople', function ($qq) use ($searchTerm) {
                        $qq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('phone', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%")
                            ->orWhere('position', 'like', "%{$searchTerm}%");
                    });
            });
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
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ];
    }
}

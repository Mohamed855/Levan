<?php

namespace App\DataTables\Tenants\BusinessCenter\Company;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Company\Company;
use Carbon\Carbon;

class CompaniesDataTable extends AbstractDataTable
{
    private $route = 'companies';

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
        return 'companies-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return Company::query()
            ->select(['id', 'name', 'status', 'logo_path', 'created_at'])
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
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الشركة', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'authorized_people_count', 'name' => 'authorized_people_count', 'title' => 'عدد المفوضين', 'width' => '100px'],
            ['data' => 'current_target_percentage', 'name' => 'current_target_percentage', 'title' => 'نسبة المستهدف الشهري', 'width' => '100px', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة', 'width' => '120px'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '100px', 'className' => 'text-center'],
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
                $img = tasset($row->logo_path);
                $showUrl = tenant_route_name($this->route.'.show', $row->id);

                return '<div class="d-flex align-items-center">
                    <img src="'.$img.'" alt="logo" class="rounded me-2"
                         style="width:28px;height:28px;object-fit:cover" />
                    <a href="'.$showUrl.'" class="text-body fw-medium">'.e($row->name).'</a>
                </div>';
            })
            ->editColumn('authorized_people_count', function ($row) {
                return '<span class="badge bg-label-info">'.($row->authorized_people_count ?? 0).'</span>';
            })
            ->editColumn('status', function ($row) {
                $canEdit = auth()->user()?->can('companies.edit');
                if ($canEdit) {
                    $checked = $row->status === 'active' ? 'checked' : '';
                    $url = tenant_route_name('companies.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" data-active-value="active" data-inactive-value="inactive" '.$checked.' aria-label="تبديل حالة الشركة"></div></div>';
                }

                $statusClass = $row->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $row->status === 'active' ? 'نشط' : 'غير نشط';

                return '<span class="badge '.$statusClass.'" title="للقراءة فقط">'.$statusText.'</span>';
            })
            ->editColumn('current_target_percentage', function ($row) {
                return $row->current_target_percentage !== null ? $row->current_target_percentage.'%' : 'غير محدد';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d');
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.business_center.companies.action', ['id' => $row->id, 'route' => $this->route])->render();
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
            $this->getAddButton(),
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

        if (auth()->user()?->can('companies.delete')) {
            $buttons[] = [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => 'function() { handleMassDelete(); }',
            ];
        }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Add Button
    |--------------------------------------------------------------------------
    | Define the primary 'Add Company' button.
    */
    private function getAddButton(): array
    {
        if (! auth()->user()?->can('companies.create')) {
            return [];
        }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة شركة',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
        ];
    }
}

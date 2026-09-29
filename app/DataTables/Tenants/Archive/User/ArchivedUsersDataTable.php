<?php

namespace App\DataTables\Tenants\Archive\User;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\UserMangment\User\User;
use Carbon\Carbon;

class ArchivedUsersDataTable extends AbstractDataTable
{
    private $route = "archive.users";

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
        return "archived-users-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        return User::onlyTrashed()
            ->select(['id', 'name', 'employee_number', 'email', 'phone', 'status', 'image', 'deleted_at', 'created_at'])
            ->with(['roles:id,name']);
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
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'user_info', 'name' => 'name', 'title' => 'اسم المستخدم', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'employee_number', 'name' => 'employee_number', 'title' => 'الرقم الوظيفي', 'width' => '120px'],
            ['data' => 'email', 'name' => 'email', 'title' => 'البريد الإلكتروني', 'width' => '180px'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'رقم الهاتف', 'width' => '120px'],
            ['data' => 'roles', 'name' => 'roles', 'title' => 'الأدوار', 'width' => '120px', 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '100px'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الأرشفة', 'width' => '120px'],
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
            ->editColumn('user_info', function ($row) {
                $avatar = $row->profile_photo_url;
                return '<div class="d-flex align-items-center">
                    <div class="avatar-wrapper me-2">
                        <div class="avatar avatar-sm">
                            <img src="' . $avatar . '" alt="Avatar" class="rounded-circle">
                        </div>
                    </div>
                    <span class="text-body fw-medium">' . $row->name . '</span>
                </div>';
            })
            ->editColumn('employee_number', function ($row) {
                return '<span class="badge bg-label-primary">' . ($row->employee_number ?? '-') . '</span>';
            })
            ->editColumn('email', function ($row) {
                return '<span class="text-primary">' . ($row->email ?? '-') . '</span>';
            })
            ->editColumn('phone', function ($row) {
                return '<span class="text-body">' . ($row->phone ?? '-') . '</span>';
            })
            ->editColumn('roles', function ($row) {
                if ($row->roles && $row->roles->count() > 0) {
                    $roleNames = $row->roles->pluck('name')->toArray();
                    return '<span class="text-info">' . implode(', ', $roleNames) . '</span>';
                }
                return '<span class="text-muted">-</span>';
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
                return view('tenants.archive.users.action', ['id' => $row->id, 'route' => $this->route])->render();
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
        return ['checkbox', 'user_info', 'employee_number', 'email', 'phone', 'roles', 'status', 'actions'];
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

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
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
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('user_archive.restore')) {
            $buttons[] = [
                'text'      => 'استرجاع المحدد',
                'className' => 'btn btn-default btn-restore-selected',
                'action'    => "function() { handleMassRestore(); }",
            ];
        }

        if (auth()->user()?->can('user_archive.force_delete')) {
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

<?php

namespace App\DataTables\Tenants\UserManagement\User;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\UserMangment\User\User;

class UsersDataTable extends AbstractDataTable
{
    private $route = "users";

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
    | Get Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource()
    {
        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'employee_number',
                'status',
                'image',
                'created_at',
                'last_login_at',
                'license_seat_id'
            ])
            ->with(['roles:id,name'])
            ->accessible();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Table ID
    |--------------------------------------------------------------------------
    */
    protected function getTableId(): string
    {
        return "users-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => ''],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'الاسم'],
            ['data' => 'employee_number', 'name' => 'employee_number', 'title' => 'الرقم الوظيفي'],
            ['data' => 'email', 'name' => 'email', 'title' => 'البريد الإلكتروني'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'رقم الهاتف'],
            ['data' => 'roles', 'name' => 'roles.name', 'title' => 'الأدوار', 'orderable' => false, 'searchable' => true],
            ['data' => 'license_status', 'name' => 'license_status', 'title' => 'الترخيص', 'orderable' => false, 'searchable' => false],
            ['data' => 'last_login_at', 'name' => 'last_login_at', 'title' => 'آخر دخول'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'action', 'name' => 'action', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Modifications
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('created_at', fn(User $user) => $user->created_at->locale('ar')->translatedFormat('d F Y'))
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
            ->addColumn('image', function (User $user) {
                $profilePicture = tasset($user->image);
                return '<img src="' . $profilePicture . '" alt="' . $user->name . '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">';
            })
            ->editColumn('name', function (User $user) {
                $url = tenant_route_name('users.show', $user->id);
                return '<a href="' . $url . '" class="text-primary fw-bold">' . e($user->name) . '</a>';
            })
            ->editColumn('employee_number', fn(User $user) => '<span class="badge bg-secondary">' . e($user->employee_number ?? '-') . '</span>')
            ->editColumn('email', fn(User $user) => '<a href="mailto:' . $user->email . '">' . $user->email . '</a>')
            ->addColumn('roles', function (User $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }
                return $user->roles->map(fn($role) => '<span class="badge bg-label-info me-1">' . $role->name . '</span>')->implode('');
            })
            ->editColumn('status', function (User $user) {
                $checked = $user->status === 'active' ? 'checked' : '';
                $url = tenant_route_name('users.toggle-status', ['user' => $user->id]);
                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input js-table-status-toggle"
                                id="status_' . $user->id . '"
                                data-url="' . e($url) . '"
                                ' . $checked . '
                                aria-label="تبديل حالة المستخدم"
                            >
                        </div>
                    </div>
                ';
            })
            ->addColumn('license_status', function (User $user) {
                $checked = $user->license_seat_id != null ? 'checked' : '';
                if ($user->id === 1)
                    return 'لا يمكن التعديل';
                else
                    return
                        '
                    <div class="d-flex justify-content-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input license-toggle"
                                id="license_' . $user->id . '"
                                data-user-id="' . $user->id . '"
                                ' . $checked . '
                                onchange="toggleLicense(this, ' . $user->id . ')"
                            >
                        </div>
                    </div>
                ';
            })
            ->editColumn('last_login_at', function (User $user) {
                return $user->last_login_at
                    ? $user->last_login_at->locale('ar')->diffForHumans()
                    : 'لم يسجل دخول';
            })
            ->addColumn('action', fn($row) => view('tenants.users_management.users.action', ['id' => $row->id, 'route' => $this->route])->render());
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'employee_number', 'email', 'roles', 'status', 'license_status', 'last_login_at', 'action'];
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

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }

        if ($request->filled('employee_number')) {
            $query->where('employee_number', 'like', '%' . $request->employee_number . '%');
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
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export btn',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
                ['text' => 'حذف المحدد', 'action' => "function() { handleMassDelete(); }"]
            ],
        ];
    }

    private function getAddButton(): array
    {
        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة مستخدم',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }",
        ];
    }
}

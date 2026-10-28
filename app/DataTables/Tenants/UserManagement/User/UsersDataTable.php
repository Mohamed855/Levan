<?php

namespace App\DataTables\Tenants\UserManagement\User;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;
use App\Support\AccessLabels;

class UsersDataTable extends AbstractDataTable
{
    private $route = 'users';

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
                'status',
                'image',
                'created_at',
                'last_login_at',
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
        return 'users-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'الاسم'],
            ['data' => 'email', 'name' => 'email', 'title' => 'البريد الإلكتروني'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'رقم الهاتف'],
            ['data' => 'roles', 'name' => 'roles.name', 'title' => 'الأدوار', 'orderable' => false, 'searchable' => true],
            ['data' => 'last_login_at', 'name' => 'last_login_at', 'title' => 'آخر دخول'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
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
            ->editColumn('created_at', fn (User $user) => $user->created_at->locale('ar')->translatedFormat('d F Y'))
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('image', function (User $user) {
                $profilePicture = $user->image ? tasset($user->image) : null;

                return $this->renderTableImage($profilePicture, (string) $user->name, 40);
            })
            ->editColumn('name', function (User $user) {
                $url = tenant_route_name('users.show', $user->id);

                return '<a href="'.$url.'" class="text-primary fw-bold">'.e($user->name).'</a>';
            })
            ->editColumn('email', fn (User $user) => '<a href="mailto:'.$user->email.'">'.$user->email.'</a>')
            ->addColumn('roles', function (User $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }

                return $user->roles->map(fn ($role) => '<span class="badge bg-info me-1">'.e(AccessLabels::role($role->name)).'</span>')->implode('');
            })
            ->editColumn('status', function (User $user) {
                $checked = $user->status === 'active' ? 'checked' : '';

                if ($user->isAdmin()) {
                    return '
                        <div class="d-flex justify-content-center" data-bs-toggle="tooltip" title="لا يمكن تغيير حالة مدير النظام">
                            <div class="form-check form-switch mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="status_'.$user->id.'"
                                    '.$checked.'
                                    disabled
                                    aria-label="لا يمكن تغيير حالة مدير النظام"
                                >
                            </div>
                        </div>
                    ';
                }

                $url = tenant_route_name('users.toggle-status', ['user' => $user->id]);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input
                                type="checkbox"
                                class="form-check-input js-table-status-toggle"
                                id="status_'.$user->id.'"
                                data-url="'.e($url).'"
                                data-active-value="active"
                                data-inactive-value="inactive"
                                '.$checked.'
                                aria-label="تبديل حالة المستخدم"
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
            ->addColumn('actions', fn ($row) => view('tenant.user-management.users.action', ['id' => $row->id, 'route' => $this->route])->render());
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'email', 'roles', 'status', 'last_login_at', 'actions'];
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
                ['text' => 'حذف المحدد', 'className' => 'btn btn-default btn-delete-selected', 'action' => 'function() { handleMassDelete(); }'],
            ],
        ];
    }

    private function getAddButton(): array
    {
        $canAdd = app(CentralSubscriptionContextGateway::class)
            ->canAddUser(User::query()->count());

        if (! $canAdd) {
            return [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة مستخدم',
                'className' => 'btn btn-secondary btn-add disabled',
                'action' => "function(){ toastr.error('لا يمكن إضافة مستخدم جديد، لقد تم الوصول إلى الحد الأقصى للمستخدمين المسموح به في باقتك.'); }",
            ];
        }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة مستخدم',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
        ];
    }
}

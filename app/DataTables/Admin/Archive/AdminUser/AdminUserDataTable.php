<?php

namespace App\DataTables\Admin\Archive\AdminUser;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\AdminMangment\Users\AdminUser;

class AdminUserDataTable extends AbstractDataTable
{
    private $page   = "admin.archive.admin_user";
    private $route  = "admin.archives";

    protected function getTableId()
    {
        return "admin-user-archive-table";
    }

    protected function resource()
    {
        return AdminUser::onlyTrashed()->with('roles');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '',  'orderable' => false, 'searchable' => false],

            ['data' => 'id',        'name' => 'id',         'title' => 'المعرف', 'visible' => false, 'className' => 'no-export'],
            ['data' => 'checkbox',  'name' => 'checkbox',   'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image',     'name' => 'image',  'className' => 'no-export',    'title' => 'الصورة',    'orderable' => false, 'searchable' => false],
            ['data' => 'name',      'name' => 'name',       'title' => 'الاسم'],
            ['data' => 'email',     'name' => 'email',      'title' => 'البريد الإلكتروني'],
            ['data' => 'phone',     'name' => 'phone',      'title' => 'رقم الهاتف'],
            ['data' => 'roles',     'name' => 'roles.name', 'title' => 'الأدوار',   'orderable' => false, 'searchable' => true],
            ['data' => 'last_login_at', 'name' => 'last_login_at', 'title' => 'آخر دخول'],
            ['data' => 'status',    'name' => 'status',     'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions',        'name' => 'actions',            'title' => 'الإجراءات', 'className' => 'no-export',     'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }


    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')

            ->addColumn('image', function (AdminUser $user) {
                $profilePicture = $user->image ? asset('storage/' . $user->image) : asset('static/brand/logos/logo.svg');
                return '<img src="' . $profilePicture . '" alt="' . $user->name . '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">';
            })
            // ->editColumn('name', function (AdminUser $user) {
            //     $url = route('admin.users.show', $user->id);
            //     return '<a href="' . $url . '" class="text-primary fw-bold">' . e($user->name) . '</a>';
            // })

            ->addColumn('roles', function (AdminUser $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }
                return $user->roles->map(fn($role) => '<span class="badge bg-label-info me-1">' . $role->name . '</span>')->implode('');
            })

            ->editColumn('status', function ($row) {
                if ($row->isSuperAdmin()) {
                    return '<span class="badge bg-primary">سوبر أدمن</span>';
                }
                return $this->UserStatusBadge($row);
            })

            ->editColumn('last_login_at', function (AdminUser $user) {
                return $user->last_login_at
                    ? $user->last_login_at->locale('ar')->diffForHumans()
                    : '-';
            })

            ->addColumn('actions', function ($row) {
                return view('admin.archive.admin_user.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

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

    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'email',  'roles', 'status', 'last_login_at', 'actions'];
    }


    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'name',
        ];
    }


    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }


    // Buttons
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
            ['extend' => 'copy', 'text'     => 'نسخ'],
            [
                'extend' => 'excel',
                'text' => 'إكسل',
                'exportOptions' => [
                    'columns' => [9, 8, 7, 6, 5, 4]
                ]
            ],
        ];

        if (auth()->user()?->can('users.delete')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); }",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        return [];
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function UserStatusBadge($row)
    {
        if (auth()->user()?->can('users.change_status')) {
          
            return sprintf(
                '<span class="badge bg-%s">%s</span>',
                $row->status->color(),
                $row->status->label()
            );
        }
    }
}

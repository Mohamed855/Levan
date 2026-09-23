<?php

namespace App\DataTables\Admin\AdminMangment\Users;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\AdminMangment\Users\AdminUser;

class AdminUserDataTable extends AbstractDataTable
{
    private $page  = "admin.admin_mangment.users";
    private $route = "admin.users";


    // table id
    protected function getTableId()
    {
        return "users-table";
    }

    protected function resource()
    {
        return AdminUser::query();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '',  'orderable' => false, 'searchable' => false],

            ['data' => 'id',        'name' => 'id',         'title' => 'ID', 'visible' => false],
            ['data' => 'checkbox',  'name' => 'checkbox',   'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image',     'name' => 'image',      'title' => 'الصورة',    'orderable' => false, 'searchable' => false],
            ['data' => 'name',      'name' => 'name',       'title' => 'الاسم'],
            ['data' => 'email',     'name' => 'email',      'title' => 'البريد الإلكتروني'],
            ['data' => 'phone',     'name' => 'phone',      'title' => 'رقم الهاتف'],
            ['data' => 'roles',     'name' => 'roles.name', 'title' => 'الأدوار',   'orderable' => false, 'searchable' => true],
            ['data' => 'status',    'name' => 'status',     'title' => 'الحالة'],
            ['data' => 'last_login_at', 'name' => 'last_login_at', 'title' => 'آخر دخول'],
            ['data' => 'actions',        'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }


    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')

            ->addColumn('image', function (AdminUser $user) {
                $profilePicture = $user->image ? asset('storage/' . $user->image) : asset('assets/img/branding/logo.svg');
                return '<img src="' . $profilePicture . '" alt="' . $user->name . '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">';
            })
            // ->editColumn('name', function (AdminUser $user) {
            //     $url = route('admin.users.show', $user->id);
            //     return '<a href="' . $url . '" class="text-primary fw-bold">' . e($user->name) . '</a>';
            // })

            ->addColumn('roles', function (AdminUser $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">لا يوجد دور</span>';
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
                    : 'لم يسجل دخول';
            })

            ->addColumn('actions', function ($row) {
                if ($row->isSuperAdmin()) {
                    return '<span class="text-muted">محمي</span>';
                }
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
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
            ['extend' => 'excel', 'text'    => 'إكسل', 'exportOptions' => [
                'columns' => [9, 8, 7, 6, 5, 4]
            ]],
        ];

        if (auth()->user()?->can('حذف مستخدم')) {
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
        if (auth()->user()?->can('إضافة مستخدم')) {
            return [
                'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة مستخدم',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
            ];
        } else {
            return [];
        }
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function UserStatusBadge($row): string
    {
        if (auth()->user()?->can('تغيير حالة المستخدم')) {
            return sprintf(
                '<span
    title="اضغط لتبديل الحالة"
    class="badge js-toggle-btn cursor-pointer bg-%s"
    data-id="' . $row->id . '"
    data-url="' . route('admin.users.toggle-status', $row->id) . '">
    %s
</span>',

                $row->status->color(),
                $row->status->label()
            );
        } else {
            return sprintf(
                '<span class="badge bg-%s">%s</span>',
                $row->status->color(),
                $row->status->label()
            );
        }
    }
}

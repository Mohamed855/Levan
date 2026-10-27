<?php

namespace App\DataTables\Admin\AdminMangment\Users;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Support\AccessLabels;

class AdminUserDataTable extends AbstractDataTable
{
    private $page = 'admin.users';

    private $route = 'admin.users';

    // table id
    protected function getTableId()
    {
        return 'users-table';
    }

    protected function resource()
    {
        return AdminUser::with('roles');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox',  'name' => 'checkbox',   'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image',     'name' => 'image',      'title' => 'الصورة',    'orderable' => false, 'searchable' => false],
            ['data' => 'name',      'name' => 'name',       'title' => 'الاسم'],
            ['data' => 'email',     'name' => 'email',      'title' => 'البريد الإلكتروني'],
            ['data' => 'phone',     'name' => 'phone',      'title' => 'رقم الهاتف'],
            ['data' => 'roles',     'name' => 'roles.name', 'title' => 'الأدوار',   'orderable' => false, 'searchable' => true],
            ['data' => 'last_login_at', 'name' => 'last_login_at', 'title' => 'آخر دخول'],
            ['data' => 'status',    'name' => 'status',     'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions',        'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

            ->addColumn('image', function (AdminUser $user) {
                $profilePicture = $user->image ? asset('storage/'.$user->image) : null;

                return $this->renderTableImage($profilePicture, (string) $user->name, 40);
            })
            // ->editColumn('name', function (AdminUser $user) {
            //     $url = route('admin.users.show', $user->id);
            //     return '<a href="' . $url . '" class="text-primary fw-bold">' . e($user->name) . '</a>';
            // })

            ->addColumn('roles', function (AdminUser $user) {
                $roleBadges = $user->roles->isEmpty()
                    ? '<span class="text-muted">-</span>'
                    : $user->roles->map(fn ($role) => '<span class="badge bg-info me-1">'.e(AccessLabels::role($role->name)).'</span>')->implode('');

                if ($user->isSuperAdmin() || ! auth('admin')->user()?->can('roles.assign')) {
                    return $roleBadges;
                }

                return '<button type="button" class="btn btn-link p-0 edit-role-link" data-user-id="'.e((string) $user->id).'" aria-label="تعديل دور المستخدم">'.$roleBadges.'</button>';
            })

            ->editColumn('status', function ($row) {
                if ($row->isSuperAdmin()) {
                    return '<div class="d-flex justify-content-center"><span class="badge bg-primary">سوبر أدمن</span></div>';
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

                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
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
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [9, 8, 7, 6, 5, 4],
            ]],
        ];

        if (auth()->user()?->can('users.delete')) {
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

    private function getAddButton(): array
    {
        if (auth()->user()?->can('users.create')) {
            return [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة مستخدم',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
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
        $isActive = ($row->status === AdminUserStatus::ACTIVE || ($row->status?->value ?? $row->status) === 'active');
        $checked = $isActive ? 'checked' : '';

        if (auth()->user()?->can('users.change_status')) {
            $url = route('admin.users.toggle-status', $row->id);

            return '
                <div class="d-flex justify-content-center">
                    <div class="form-check form-switch mb-0">
                        <input
                            type="checkbox"
                            class="form-check-input js-table-status-toggle"
                            id="status_'.$row->id.'"
                            data-url="'.$url.'"
                            '.$checked.'
                            aria-label="تبديل حالة المستخدم"
                        >
                    </div>
                </div>
            ';
        } else {
            return sprintf(
                '<div class="d-flex justify-content-center"><span class="badge bg-%s">%s</span></div>',
                $row->status->color(),
                $row->status->label()
            );
        }
    }
}

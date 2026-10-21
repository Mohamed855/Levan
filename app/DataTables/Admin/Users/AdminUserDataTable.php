<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Users;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Models\Admin\AdminUser;

class AdminUserDataTable extends AbstractDataTable
{
    private string $page = 'admin.users';

    private string $route = 'admin.users';

    protected function getTableId(): string
    {
        return 'users-table';
    }

    protected function resource()
    {
        return AdminUser::query()->with('roles');
    }

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
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('image', function (AdminUser $user) {
                $profilePicture = $user->image ? asset('storage/'.$user->image) : null;
                return $this->renderTableImage($profilePicture, (string) $user->name, 40);
            })
            ->addColumn('roles', function (AdminUser $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }

                return $user->roles->map(function ($role) {
                    $label = $role->display_name ?: $role->name;
                    return '<span class="badge bg-label-info me-1">'.e($label).'</span>';
                })->implode('');
            })
            ->editColumn('status', function ($row) {
                if ($row->isSuperAdmin()) {
                    return '<div class="d-flex justify-content-center"><span class="badge bg-primary">سوبر أدمن</span></div>';
                }

                return $this->userStatusBadge($row);
            })
            ->editColumn('last_login_at', function (AdminUser $user) {
                return $user->last_login_at
                    ? $user->last_login_at->locale('ar')->diffForHumans()
                    : 'لم يسجل دخول';
            })
            ->addColumn('actions', function ($row) {
                if ($row->isSuperAdmin() && auth('admin')->id() !== $row->id) {
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
        return ['checkbox', 'image', 'name', 'email', 'roles', 'status', 'last_login_at', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'email', 'phone'];
    }

    protected function getCustomOrder(): array
    {
        return [2, 'asc'];
    }

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
                'columns' => [2, 3, 4, 5, 6, 7],
            ]],
        ];

        if (auth('admin')->user()?->can('admin_users.update') || auth('admin')->user()?->can('users.delete')) {
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
        if (auth('admin')->user()?->can('admin_users.create') || auth('admin')->user()?->can('users.create')) {
            return [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة مستخدم',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }

    private function userStatusBadge($row): string
    {
        $isActive = ($row->status === AdminUserStatus::ACTIVE || ($row->status?->value ?? $row->status) === 'active');
        $checked = $isActive ? 'checked' : '';

        if (auth('admin')->user()?->can('admin_users.update') || auth('admin')->user()?->can('users.change_status')) {
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
        }

        $badgeColor = $isActive ? 'success' : 'danger';
        $badgeLabel = $isActive ? 'نشط' : 'غير نشط';

        return sprintf(
            '<div class="d-flex justify-content-center"><span class="badge bg-%s">%s</span></div>',
            $badgeColor,
            $badgeLabel
        );
    }
}

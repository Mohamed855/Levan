<?php

namespace App\DataTables\Admin\Archive\AdminUser;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Support\AccessLabels;

class AdminUserDataTable extends AbstractDataTable
{
    private $page = 'admin.archive.admin_user';

    private $route = 'admin.archives';

    protected function getTableId()
    {
        return 'admin-user-archive-table';
    }

    protected function resource()
    {
        return AdminUser::onlyTrashed()->with('roles');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox',  'name' => 'checkbox',   'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image',     'name' => 'image',  'className' => 'no-export text-center',    'title' => 'الصورة',    'orderable' => false, 'searchable' => false],
            ['data' => 'name',      'name' => 'name',       'title' => 'الاسم', 'className' => 'text-center'],
            ['data' => 'email',     'name' => 'email',      'title' => 'البريد الإلكتروني', 'className' => 'text-center'],
            ['data' => 'phone',     'name' => 'phone',      'title' => 'رقم الهاتف', 'className' => 'text-center'],
            ['data' => 'roles',     'name' => 'roles.name', 'title' => 'الأدوار',   'orderable' => false, 'searchable' => true, 'className' => 'text-center'],
            ['data' => 'last_login_at', 'name' => 'last_login_at', 'title' => 'آخر دخول', 'className' => 'text-center'],
            ['data' => 'status',    'name' => 'status',     'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف', 'className' => 'text-center', 'width' => '130px'],
            ['data' => 'actions',        'name' => 'actions',            'title' => 'الإجراءات', 'className' => 'no-export text-center',     'orderable' => false, 'searchable' => false, 'width' => '120px'],
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
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }

                return $user->roles->map(fn ($role) => '<span class="badge bg-info me-1">'.e(AccessLabels::role($role->name)).'</span>')->implode('');
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

            ->editColumn('deleted_at', fn ($row): string => $this->formatDate($row->deleted_at))

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
        return [8, 'desc'];
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
            $this->copyActionButton(),
            $this->excelActionButton(),
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

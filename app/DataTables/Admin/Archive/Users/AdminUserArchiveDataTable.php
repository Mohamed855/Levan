<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Archive\Users;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\AdminUser;

class AdminUserArchiveDataTable extends AbstractDataTable
{
    private string $page = 'admin.archives.users';

    private string $route = 'admin.archives.users';

    protected function getTableId(): string
    {
        return 'users-archive-table';
    }

    protected function resource()
    {
        return AdminUser::onlyTrashed()->with('roles');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المستخدم'],
            ['data' => 'email', 'name' => 'email', 'title' => 'البريد الإلكتروني'],
            ['data' => 'role', 'name' => 'roles.name', 'title' => 'الدور الإداري', 'orderable' => false],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('email', fn ($row) => '<span class="font-monospace text-muted">'.e($row->email).'</span>')
            ->addColumn('role', function ($row) {
                $role = $row->roles->first();
                return $role ? '<span class="badge bg-label-primary">'.e($role->name).'</span>' : '<span class="text-muted">-</span>';
            })
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'email', 'role', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'email'];
    }

    protected function getCustomOrder(): array
    {
        return [4, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

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

    protected function getAddButton(): array
    {
        return [];
    }
}

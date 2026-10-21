<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Roles;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Access\Role;

class TenantRoleDataTable extends AbstractDataTable
{
    private string $page = 'tenant.roles';

    private string $route = 'tenant.roles';

    protected function getTableId(): string
    {
        return 'tenant-roles-table';
    }

    protected function resource()
    {
        return Role::query()
            ->where('guard', 'tenant')
            ->withCount(['tenantUsers as users_count', 'permissions as permissions_count']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الدور'],
            ['data' => 'users_count', 'name' => 'users_count', 'title' => 'المستخدمون', 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'permissions_count', 'name' => 'permissions_count', 'title' => 'الصلاحيات', 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($dataTable): void
    {
        $dataTable
            ->addColumn('checkbox', fn (Role $role): string => '<input type="checkbox" class="row-checkbox" value="'.$role->id.'">')
            ->editColumn('name', function (Role $role): string {
                $displayName = $role->display_name ?: $role->name;
                $url = route($this->route.'.show', $role->id);
                return '<a href="'.$url.'" class="fw-medium text-heading">'.e($displayName).'</a> <small class="text-muted d-block font-monospace">'.e($role->name).'</small>';
            })
            ->editColumn('users_count', fn (Role $role): string => '<span class="badge bg-label-primary">'.number_format((int) $role->users_count).'</span>')
            ->editColumn('permissions_count', fn (Role $role): string => '<span class="badge bg-label-info">'.number_format((int) $role->permissions_count).'</span>')
            ->editColumn('created_at', fn (Role $role): string => $role->created_at?->locale('ar')->translatedFormat('d F Y') ?? '-')
            ->addColumn('actions', fn (Role $role): string => view($this->page.'.action', ['role' => $role, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'users_count', 'permissions_count', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'display_name'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'asc'];
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
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                    'columns' => [1, 2, 3, 4],
                ]],
            ],
        ];
    }

    private function getAddButton(): array
    {
        if (auth('tenant')->user()?->can('roles.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة دور',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}

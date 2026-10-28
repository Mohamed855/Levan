<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\UserManagement\Role;

use App\DataTables\Abstract\AbstractDataTable;
use App\Support\AccessLabels;
use Spatie\Permission\Models\Role;

final class RoleDataTable extends AbstractDataTable
{
    private string $page = 'tenant.user-management.roles';

    private string $route = 'roles';

    protected function getTableId(): string
    {
        return 'roles-table';
    }

    protected function resource()
    {
        return Role::query()
            ->where('guard_name', 'tenant')
            ->withCount(['users', 'permissions']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الدور'],
            ['data' => 'users_count', 'name' => 'users_count', 'title' => 'المستخدمون', 'searchable' => false],
            ['data' => 'permissions_count', 'name' => 'permissions_count', 'title' => 'الصلاحيات', 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($dataTable): void
    {
        $dataTable
            ->addColumn('checkbox', fn (Role $role): string => '<input type="checkbox" class="row-checkbox" value="'.$role->id.'">')
            ->editColumn('name', fn (Role $role): string => '<span class="fw-medium text-heading">'.e(AccessLabels::role($role->name)).'</span>')
            ->editColumn('users_count', fn (Role $role): string => '<span class="badge bg-primary">'.number_format($role->users_count).'</span>')
            ->editColumn('permissions_count', fn (Role $role): string => '<span class="badge bg-info">'.number_format($role->permissions_count).'</span>')
            ->editColumn('created_at', fn (Role $role): string => $role->created_at?->locale('ar')->translatedFormat('d F Y') ?? '-')
            ->addColumn('actions', fn (Role $role): string => view($this->page.'.action', compact('role'))->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'users_count', 'permissions_count', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('role')) {
            $query->where('id', $request->role);
        }

        if ($request->filled('usage_status')) {
            $usage = $request->usage_status;
            if ($usage === 'assigned') {
                $query->has('users');
            } elseif ($usage === 'unassigned') {
                $query->doesntHave('users');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    protected function getCustomOrder(): array
    {
        return [1, 'asc'];
    }

    protected function buttons(): array
    {
        return [$this->getActionsDropdownButton(), $this->getAddButton()];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('tenant')->user()?->can('roles.delete')) {
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
        if (! auth('tenant')->user()?->can('roles.create')) {
            return [];
        }

        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة دور',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
        ];
    }
}

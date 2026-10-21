<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Users;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\User;

class TenantUserDataTable extends AbstractDataTable
{
    private string $page = 'tenant.users';

    private string $route = 'tenant.users';

    protected function getTableId(): string
    {
        return 'tenant-users-table';
    }

    protected function resource()
    {
        return User::query()->with('roles');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المستخدم'],
            ['data' => 'email', 'name' => 'email', 'title' => 'البريد الإلكتروني'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'رقم الجوال'],
            ['data' => 'roles', 'name' => 'roles.name', 'title' => 'الأدوار', 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('name', function (User $row) {
                $url = route($this->route.'.show', $row->id);
                return '<a href="'.$url.'" class="fw-bold text-heading">'.e($row->name).'</a>';
            })
            ->editColumn('email', fn ($row) => '<span class="font-monospace text-muted">'.e($row->email).'</span>')
            ->editColumn('phone', fn ($row) => $row->phone ? '<span class="font-monospace" dir="ltr">'.e($row->phone).'</span>' : '<span class="text-muted">-</span>')
            ->addColumn('roles', function (User $row) {
                if ($row->roles->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }

                return $row->roles->map(fn ($r) => '<span class="badge bg-label-info me-1">'.e($r->display_name ?: $r->name).'</span>')->implode('');
            })
            ->editColumn('status', function (User $row) {
                if (! auth('tenant')->user()?->can('users.update')) {
                    return $row->status === 'active' ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">معطل</span>';
                }

                $checked = $row->status === 'active' ? 'checked' : '';
                $url = route('tenant.users.toggle-status', $row->id);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة المستخدم">
                        </div>
                    </div>';
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $val = $request->status;
            if ($val === 'active' || $val === 'inactive') {
                $query->where('status', $val);
            }
        }

        if ($request->filled('role_id')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $request->role_id));
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'roles', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'email', 'phone'];
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
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [1, 2, 3, 4],
            ]],
        ];

        if (auth('tenant')->user()?->can('users.delete')) {
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
        if (auth('tenant')->user()?->can('users.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة مستخدم',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}

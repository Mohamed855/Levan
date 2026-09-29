<?php

namespace App\DataTables\Admin\VersionTester;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Version\VersionTester;

class VersionTesterDataTable extends AbstractDataTable
{
    private $page  = 'admin.version_testers';
    private $route = 'admin.version_testers';

    protected function getTableId()
    {
        return 'version-testers-table';
    }

    protected function resource()
    {
        return VersionTester::with('tenant');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width'      => '10px',
                'className'  => 'custom-checkbox',
                'titleAttr'  => 'تحديد الكل',
            ],
            ['data' => 'tenant_name',   'name' => 'tenant.name',    'title' => 'اسم العميل',    'className' => 'text-start'],
            ['data' => 'type',          'name' => 'type',           'title' => 'نوع المختبر',   'className' => 'text-center'],
            ['data' => 'is_active',     'name' => 'is_active',      'title' => 'الحالة',        'className' => 'text-center'],
            ['data' => 'created_at',    'name' => 'created_at',     'title' => 'تاريخ الإضافة'],
            ['data' => 'actions',       'name' => 'actions',        'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')

            ->addColumn('tenant_name', fn($row) => $row->tenant->company_name ?? '-')

            ->editColumn('type', fn($row) => $this->typeBadge($row->type))

            ->editColumn(
                'is_active',
                fn($row) => $row->is_active
                    ? '<span class="badge bg-success">مفعل</span>'
                    : '<span class="badge bg-danger">غير مفعل</span>'
            )

            ->editColumn('created_at', fn($row) => $row->created_at->format('Y-m-d H:i'))

            ->addColumn('actions', fn($row) => view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'type', 'is_active', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['tenant.name', 'type'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
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
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('version_testers.delete')) {
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
        if (auth()->user()?->can('version_testers.create')) {
            return [
                'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة عضو',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
            ];
        } else {
            return [];
        }
    }

    private function typeBadge($type): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $type->color(),
            $type->label()
        );
    }
}

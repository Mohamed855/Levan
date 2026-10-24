<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Archive\Tenants;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Tenants\Tenant;

class TenantArchiveDataTable extends AbstractDataTable
{
    private string $page = 'admin.archives.tenants';

    private string $route = 'admin.archives.tenants';

    protected function getTableId(): string
    {
        return 'tenants-archive-table';
    }

    protected function resource()
    {
        return Tenant::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'company_name', 'name' => 'company_name', 'title' => 'اسم المستأجر / الجهة'],
            ['data' => 'id', 'name' => 'id', 'title' => 'معرف المستأجر'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('company_name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->company_name ?? $row->id).'</span>')
            ->editColumn('id', fn ($row) => '<span class="font-monospace text-muted" dir="ltr">'.e($row->id).'</span>')
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'company_name', 'id', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['company_name', 'id'];
    }

    protected function getCustomOrder(): array
    {
        return [3, 'desc'];
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

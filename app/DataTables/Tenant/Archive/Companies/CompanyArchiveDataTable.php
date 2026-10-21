<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Archive\Companies;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Company;

class CompanyArchiveDataTable extends AbstractDataTable
{
    private string $page = 'tenant.archives.companies';

    private string $route = 'tenant.archives.companies';

    protected function getTableId(): string
    {
        return 'companies-archive-table';
    }

    protected function resource()
    {
        return Company::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الشركة'],
            ['data' => 'commercial_number', 'name' => 'commercial_number', 'title' => 'السجل التجاري'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'رقم الهاتف'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('commercial_number', fn ($row) => '<span class="font-monospace">'.e($row->commercial_number ?? '-').'</span>')
            ->editColumn('phone', fn ($row) => e($row->phone ?? '-'))
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'commercial_number', 'phone'];
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

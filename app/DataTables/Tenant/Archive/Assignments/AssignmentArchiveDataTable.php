<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Archive\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Assignment;

class AssignmentArchiveDataTable extends AbstractDataTable
{
    private string $page = 'tenant.archives.assignments';

    private string $route = 'tenant.archives.assignments';

    protected function getTableId(): string
    {
        return 'assignments-archive-table';
    }

    protected function resource()
    {
        return Assignment::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'assignment_number', 'name' => 'assignment_number', 'title' => 'رقم الإسناد'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الإسناد'],
            ['data' => 'issuing_entity', 'name' => 'issuing_entity', 'title' => 'الجهة المصدرة'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('assignment_number', fn ($row) => '<span class="font-monospace fw-bold text-primary">#'.e($row->assignment_number).'</span>')
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('issuing_entity', fn ($row) => e($row->issuing_entity ?? '-'))
            ->editColumn('city', fn ($row) => e($row->city ?? '-'))
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'assignment_number', 'name', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['assignment_number', 'name', 'issuing_entity'];
    }

    protected function getCustomOrder(): array
    {
        return [5, 'desc'];
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

<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Archive\Plans;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Plans\Plan;

class PlanArchiveDataTable extends AbstractDataTable
{
    private string $page = 'admin.archives.plans';

    private string $route = 'admin.archives.plans';

    protected function getTableId(): string
    {
        return 'plans-archive-table';
    }

    protected function resource()
    {
        return Plan::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الباقة'],
            ['data' => 'price', 'name' => 'price', 'title' => 'السعر'],
            ['data' => 'period', 'name' => 'period', 'title' => 'المدة'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('price', fn ($row) => number_format((float) $row->price, 2).' ر.س')
            ->editColumn('period', fn ($row) => $row->period === 'yearly' ? 'سنوي' : 'شهري')
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
        return ['name'];
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

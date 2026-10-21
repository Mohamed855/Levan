<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Archive\Properties;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Property;

class PropertyArchiveDataTable extends AbstractDataTable
{
    private string $page = 'tenant.archives.properties';

    private string $route = 'tenant.archives.properties';

    protected function getTableId(): string
    {
        return 'properties-archive-table';
    }

    protected function resource()
    {
        return Property::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم العقار'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم العقار'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'area', 'name' => 'area', 'title' => 'المساحة'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('property_number', fn ($row) => '<span class="font-monospace fw-bold text-primary">#'.e($row->property_number).'</span>')
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('city', fn ($row) => e($row->city ?? '-'))
            ->editColumn('area', fn ($row) => $row->area ? number_format((float) $row->area, 2).' م²' : '-')
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'property_number', 'name', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['property_number', 'name', 'city'];
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

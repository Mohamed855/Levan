<?php

namespace App\DataTables\Tenants\Properties;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Properties\Property;

class PropertyArchiveDataTable extends AbstractDataTable
{
    private string $route = 'archive.properties';

    protected function getTableId(): string
    {
        return 'properties-archive-table';
    }

    protected function resource()
    {
        return Property::onlyTrashed()->with('assignment');
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        return $query
            ->when($request->filled('deleted_at'), fn ($q) => $q->whereDate('deleted_at', $request->deleted_at));
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'width' => '10px', 'className' => 'custom-checkbox'],
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم الأصل', 'className' => 'text-center'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم العقار', 'className' => 'text-center'],
            ['data' => 'assignment_name', 'name' => 'assignment.name', 'title' => 'الإسناد التابع له', 'className' => 'text-center'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة', 'className' => 'text-center'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف', 'className' => 'text-center', 'width' => '130px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px', 'className' => 'text-center'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row): string => $this->checkbox($row))
            ->addColumn('assignment_name', function ($row) {
                return $row->assignment?->name ?? '-';
            })
            ->editColumn('deleted_at', function ($row) {
                return $this->formatDate($row->deleted_at);
            })
            ->addColumn('actions', function ($row) {
                return view('tenant.archive.properties.action', ['row' => $row])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'actions'];
    }

    protected function getCustomOrder(): array
    {
        return [5, 'desc'];
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
        return $this->actionsCollectionButton([
            $this->copyActionButton(),
            $this->excelActionButton(),
        ])[0];
    }

    private function getAddButton(): array
    {
        return [];
    }
}

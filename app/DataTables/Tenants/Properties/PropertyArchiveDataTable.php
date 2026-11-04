<?php

namespace App\DataTables\Tenants\Properties;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Properties\Property;
use Carbon\Carbon;

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
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم العقار', 'className' => 'text-center'],
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
            ->addColumn('assignment_name', function ($row) {
                return $row->assignment?->name ?? '-';
            })
            ->editColumn('deleted_at', function ($row) {
                return $row->deleted_at ? $row->deleted_at->format('Y-m-d') : '-';
            })
            ->addColumn('actions', function ($row) {
                return view('tenant.archive.properties.action', ['row' => $row])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['actions'];
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

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        return [];
    }
}

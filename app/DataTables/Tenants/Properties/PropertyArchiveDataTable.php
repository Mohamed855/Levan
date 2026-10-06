<?php

namespace App\DataTables\Tenants\Properties;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Properties\Property;
use Carbon\Carbon;

class PropertyArchiveDataTable extends AbstractDataTable
{
    private string $route = "archive.properties";

    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    protected function getTableId(): string
    {
        return "properties-archive-table";
    }

    protected function resource()
    {
        return Property::onlyTrashed()->with('assignment');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '40px'],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم العقار'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم العقار'],
            ['data' => 'assignment_name', 'name' => 'assignment.name', 'title' => 'الإسناد التابع له'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->addColumn('assignment_name', function ($row) {
                return $row->assignment?->name ?? '-';
            })
            ->editColumn('deleted_at', function ($row) {
                return $row->deleted_at ? Carbon::parse($row->deleted_at)->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.archive.properties.action', ['row' => $row])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'actions'];
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
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        return [];
    }
}

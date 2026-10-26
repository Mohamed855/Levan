<?php

namespace App\DataTables\Tenants\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Assignments\Assignment;
use Carbon\Carbon;

class AssignmentArchiveDataTable extends AbstractDataTable
{
    private string $route = 'archive.assignments';

    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    protected function getTableId(): string
    {
        return 'assignments-archive-table';
    }

    protected function resource()
    {
        return Assignment::onlyTrashed()
            ->withCount(['properties', 'auctions']);
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
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '40px'],
            ['data' => 'assignment_number', 'name' => 'assignment_number', 'title' => 'رقم الإسناد'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الإسناد'],
            ['data' => 'issuing_entity', 'name' => 'issuing_entity', 'title' => 'الجهة المسندة'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->editColumn('deleted_at', function ($row) {
                return $row->deleted_at ? Carbon::parse($row->deleted_at)->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function ($row) {
                return view('tenant.archive.assignments.action', ['row' => $row])->render();
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

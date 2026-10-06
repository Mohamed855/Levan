<?php

namespace App\DataTables\Tenants\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Assignments\Assignment;
use Carbon\Carbon;

class AssignmentArchiveDataTable extends AbstractDataTable
{
    private string $route = "archive.assignments";

    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    protected function getTableId(): string
    {
        return "assignments-archive-table";
    }

    protected function resource()
    {
        return Assignment::onlyTrashed()
            ->withCount(['properties', 'auctions']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
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
                return view('tenants.archive.assignments.action', ['row' => $row])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['actions'];
    }
}

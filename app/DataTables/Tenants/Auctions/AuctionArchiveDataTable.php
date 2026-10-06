<?php

namespace App\DataTables\Tenants\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use Carbon\Carbon;

class AuctionArchiveDataTable extends AbstractDataTable
{
    private string $route = "archive.auctions";

    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);
        return $datatable->rawColumns($this->rawColumns());
    }

    protected function getTableId(): string
    {
        return "auctions-archive-table";
    }

    protected function resource()
    {
        return Auction::onlyTrashed()->with('assignment');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المزاد'],
            ['data' => 'assignment_name', 'name' => 'assignment.name', 'title' => 'الإسناد المرتبط'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('assignment_name', function ($row) {
                return $row->assignment?->name ?? '-';
            })
            ->editColumn('deleted_at', function ($row) {
                return $row->deleted_at ? Carbon::parse($row->deleted_at)->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.archive.auctions.action', ['row' => $row])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['actions'];
    }
}

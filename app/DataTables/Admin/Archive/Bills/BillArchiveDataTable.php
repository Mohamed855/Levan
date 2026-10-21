<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Archive\Bills;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Bill;

class BillArchiveDataTable extends AbstractDataTable
{
    private string $page = 'admin.archives.bills';

    private string $route = 'admin.archives.bills';

    protected function getTableId(): string
    {
        return 'bills-archive-table';
    }

    protected function resource()
    {
        return Bill::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'bill_number', 'name' => 'bill_number', 'title' => 'رقم الفاتورة'],
            ['data' => 'tenant_id', 'name' => 'tenant_id', 'title' => 'المستأجر'],
            ['data' => 'type', 'name' => 'type', 'title' => 'نوع الفاتورة'],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'المبلغ الإجمالي'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('bill_number', fn ($row) => '<span class="font-monospace fw-bold text-heading">'.e($row->bill_number).'</span>')
            ->editColumn('tenant_id', fn ($row) => $row->tenant_id ? '<span class="badge bg-label-info">'.e($row->tenant_id).'</span>' : '<span class="text-muted">-</span>')
            ->editColumn('type', fn ($row) => '<span class="badge bg-label-primary">'.e($row->type).'</span>')
            ->editColumn('amount', fn ($row) => number_format((float) $row->amount, 2).' ر.س')
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'bill_number', 'tenant_id', 'type', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['bill_number', 'tenant_id'];
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

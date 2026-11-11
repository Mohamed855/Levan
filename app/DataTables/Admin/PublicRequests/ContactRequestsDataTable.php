<?php

declare(strict_types=1);

namespace App\DataTables\Admin\PublicRequests;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\PublicRequests\ContactRequest;

final class ContactRequestsDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'contact-requests-table';
    }

    protected function resource()
    {
        return ContactRequest::query()->latest();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'width' => '10px', 'className' => 'custom-checkbox'],
            ['data' => 'id', 'name' => 'id', 'title' => 'رقم الطلب'],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'الاسم'],
            ['data' => 'customer_email', 'name' => 'customer_email', 'title' => 'البريد'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التاريخ'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        return $datatable
            ->addColumn('checkbox', fn ($row): string => $this->checkbox($row))
            ->editColumn('status', fn ($row) => '<span class="badge bg-'.($row->status->value === 'pending' ? 'warning' : 'success').'">'.$row->status->label().'</span>')
            ->editColumn('created_at', fn ($row) => $this->formatDate($row->created_at))
            ->addColumn('actions', fn ($row) => '<div class="d-flex justify-content-center"><a class="btn btn-sm btn-icon btn-primary bg-primary" href="'.route('admin.contact-requests.show', $row).'" title="عرض"><i class="ti ti-eye"></i></a></div>');
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $query = $this->applyRequestFilters($query, ['status']);

        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return ['customer_name', 'customer_email', 'message'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    protected function buttons(): array
    {
        return $this->actionsCollectionButton([
            $this->copyActionButton(),
            $this->excelActionButton(),
        ]);
    }
}

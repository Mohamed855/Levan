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
            ->editColumn('status', fn ($row) => '<span class="badge bg-label-'.($row->status->value === 'pending' ? 'warning' : 'success').'">'.$row->status->label().'</span>')
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i'))
            ->addColumn('actions', fn ($row) => '<div class="d-flex justify-content-center"><a class="btn btn-sm btn-icon btn-label-primary bg-label-primary" href="'.route('admin.contact-requests.show', $row).'" title="عرض"><i class="ti ti-eye"></i></a></div>');
    }

    protected function rawColumns(): array
    {
        return ['status', 'actions'];
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

    protected function buttons(): array
    {
        return [[
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ]];
    }
}

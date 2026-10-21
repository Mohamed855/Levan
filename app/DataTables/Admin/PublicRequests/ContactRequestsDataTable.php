<?php

declare(strict_types=1);

namespace App\DataTables\Admin\PublicRequests;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\PublicRequests\ContactRequest;

class ContactRequestsDataTable extends AbstractDataTable
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
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'id', 'name' => 'id', 'title' => '#', 'width' => '50px'],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'الاسم'],
            ['data' => 'customer_email', 'name' => 'customer_email', 'title' => 'البريد الإلكتروني'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإرسال', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('status', function ($row) {
                $status = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;
                $color = $status === 'replied' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary');
                $label = $status === 'replied' ? 'تم الرد' : ($status === 'pending' ? 'قيد الانتظار' : $status);
                return '<span class="badge bg-label-'.$color.'">'.e($label).'</span>';
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return '<div class="d-flex justify-content-center"><a class="btn btn-sm btn-icon btn-label-primary bg-label-primary" href="'.route('admin.contact-requests.show', $row).'" title="عرض"><i class="ti ti-eye"></i></a></div>';
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
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
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('admin')->user()?->can('public_requests.process')) {
            $buttons[] = [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => 'function() { handleMassDelete(); }',
            ];
        }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}

<?php

namespace App\DataTables\Tenants\Bills;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Bills\Bill;

class BillsDataTable extends AbstractDataTable
{
    private $page = 'tenants.bills';

    private $route = 'bills';

    protected function getTableId()
    {
        return 'bill-table';
    }

    protected function resource()
    {
        $tenantId = tenant('id');

        return tenancy()->central(function () use ($tenantId) {
            return Bill::query()->where('tenant_id', $tenantId);
        });
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'visible' => false],
            [
                'data' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
                'width' => '10px',
            ],
            ['data' => 'bill_number',       'title' => 'رقم الفاتورة',     'className' => 'text-start'],
            ['data' => 'type',              'title' => 'نوع العملية',      'className' => 'text-start'],
            ['data' => 'amount',            'title' => 'المبلغ',           'className' => 'text-start'],
            ['data' => 'settlement_amount', 'title' => 'التسوية',           'className' => 'text-start'],
            ['data' => 'payment_method',    'title' => 'طريقة الدفع',   'className' => 'text-start'],
            ['data' => 'status',            'title' => 'حالة العملية',   'className' => 'text-start'],
            ['data' => 'created_at',        'title' => 'تاريخ العملية',   'className' => 'text-start'],
            ['data' => 'actions',           'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],

        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('type', function ($row) {
                return $this->TypeBadge($row);
            })

            ->editColumn('bill_number', function ($row) {
                return $this->routeName(tenant_route_name($this->route.'.show', $row->id), $row->bill_number);
            })

            ->editColumn('amount', function ($row) {
                return number_format($row->amount, 2);
            })

            ->editColumn('settlement_amount', function ($row) {
                return number_format($row->settlement_amount, 2);
            })

            ->editColumn('payment_method', function ($row) {
                return $this->PaymentMethodBadge($row);
            })

            ->editColumn('status', function ($row) {
                return $this->StatusBadge($row);
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at?->format('Y-m-d');
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'bill_number', 'type', 'payment_method', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return ['type', 'created_at'];
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

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }

    private function TypeBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->type->color(),
            $row->type->label()
        );
    }

    private function PaymentMethodBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->payment_method?->color(),
            $row->payment_method?->label()
        );
    }

    private function StatusBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->status->color(),
            $row->status->label()
        );
    }
}

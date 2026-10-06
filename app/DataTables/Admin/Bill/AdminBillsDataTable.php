<?php

namespace App\DataTables\Admin\Bill;



use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Bills\Bill;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminBillsDataTable extends AbstractDataTable
{
    private $page   = "admin.bills";
    private $route  = "admin.bills";

    protected function getTableId()
    {
        return "bill-table";
    }

    protected function resource()
    {
        return Bill::query();
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
            ['data' => 'bill_number',       'title' => 'رقم الفاتورة',],

            ['data' => 'tenant_name',       'title' => 'العميل',],
            ['data' => 'tenant_id',         'title' => 'المعرف',],
            ['data' => 'type',              'title' => 'نوع العملية',],
            ['data' => 'amount',            'title' => 'المبلغ',],
            ['data' => 'settlement_amount', 'title' => 'التسوية',],
            ['data' => 'payment_method',    'title' => 'طريقة الدفع',],
            ['data' => 'status',            'title' => 'حالة العملية',],
            ['data' => 'created_at',        'title' => 'تاريخ العملية',],
            ['data' => 'due_date',         'title' => 'اخر تاريخ للسداد',],
            ['data' => 'actions',           'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],

        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('tenant_name', function ($row) {
                return $row->tenant?->company_name ?? "-";
            })

            ->editColumn('type', function ($row) {
                return $this->TypeBadge($row);
            })

            ->editColumn('bill_number', function ($row) {
                return $this->routeName(route($this->route . '.show', $row->id), $row->bill_number);
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

            ->editColumn('due_date', function ($row) {
                if (!$row->due_date) {
                    return '-';
                }

                $isOverdue = Carbon::parse($row->due_date)->isPast()
                    && $row->status->value !== 'paid'; // تجاهل المدفوعة

                return sprintf(
                    '<span class="%s">%s</span>',
                    $isOverdue ? 'text-danger fw-bold' : '',
                    $row->due_date
                );
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type',  $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'bill_number', 'type', 'payment_method', 'status', 'due_date', 'actions'];
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
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [11, 10, 9, 8, 7, 6, 5, 4, 3]
            ]],
        ];

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
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

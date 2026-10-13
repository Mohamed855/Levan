<?php

namespace App\DataTables\Tenants\Reports\Contract;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Contract\Contract;

class ContractReportDataTable extends AbstractDataTable
{
    protected function getTableId()
    {
        return 'contract-report-table';
    }

    protected function resource()
    {
        return Contract::query();
    }

    protected function applyCustomFilters($query)
    {
        $request = request();
        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        return $query;
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
            ['data' => 'contract_number',       'title' => 'رقم العقد',     'className' => 'text-start'],
            ['data' => 'start_date',       'title' => 'تاريخ البدء',     'className' => 'text-start'],
            ['data' => 'end_date',       'title' => 'تاريخ الانتهاء',     'className' => 'text-start'],
            ['data' => 'status',       'title' => 'الحالة',     'className' => 'text-start'],

            // ['data' => 'type',              'title' => 'نوع العملية',      'className' => 'text-start'],
            // ['data' => 'amount',            'title' => 'المبلغ',           'className' => 'text-start'],
            // ['data' => 'settlement_amount', 'title' => 'التسوية',           'className' => 'text-start'],
            // ['data' => 'payment_method',    'title' => 'طريقة الدفع',   'className' => 'text-start'],
            // ['data' => 'status',            'title' => 'حالة العملية',   'className' => 'text-start'],
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

            ->editColumn('start_date', function ($row) {
                return $row->start_date?->format('Y-m-d');
            })

            ->editColumn('end_date', function ($row) {
                return $row->end_date?->format('Y-m-d');
            })

            ->editColumn('status', function ($row) {
                $class = $row->status === 'active' ? 'success' : 'secondary';
                $text = $row->status === 'active' ? 'نشط' : 'غير نشط';

                return $text;
            })

        // ->editColumn('amount', function ($row) {
        //     return number_format($row->amount, 2);
        // });

        // ->editColumn('settlement_amount', function ($row) {
        //     return number_format($row->settlement_amount, 2);
        // })

        // ->editColumn('payment_method', function ($row) {
        //     return $this->PaymentMethodBadge($row);
        // })

        // ->editColumn('status', function ($row) {
        //     return $this->StatusBadge($row);
        // })
            ->editColumn('created_at', function ($row) {
                return $row->created_at?->format('Y-m-d');
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.reports.contracts.action', ['row' => $row])->render();
            });
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'actions',
            //  'bill_number', 'type', 'payment_method', 'status'
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['contract_number'];
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

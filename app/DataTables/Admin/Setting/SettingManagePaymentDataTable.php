<?php

namespace App\DataTables\Admin\Setting;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Bills\Bill;
use App\Models\MoyasarTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SettingManagePaymentDataTable extends AbstractDataTable
{
    private $page  = "admin.paymenttransaction";
    private $route = "admin.paymentTransaction";

    // table id
    protected function getTableId()
    {
        return "manage-payment-table";
    }

    protected function resource()
    {
        return tenancy()->central(function () {
            return MoyasarTransaction::query();
        });
    }


    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],

            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width'      => '10px',
                'className'  => 'custom-checkbox',
                'titleAttr'  => 'تحديد الكل',
            ],

            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => 'اسم العميل', 'className' => 'text-start'],
            ['data' => 'payment_id', 'name' => 'payment_id', 'title' => 'رقم العملية', 'className' => 'text-start'],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'المبلغ', 'className' => 'text-start'],
            ['data' => 'description', 'name' => 'description', 'title' => 'نوع العملية', 'className' => 'text-start'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-start'],
            ['data' => 'paid_at', 'name' => 'paid_at', 'title' => 'تاريخ العملية', 'className' => 'text-start'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('paid_at', function ($row) {
                return $row->paid_at
                    ? Carbon::parse($row->paid_at)->format('Y-m-d')
                    : '-';
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'status'];
    }

    protected function getSearchableColumns(): array
    {
        return ['customer_name', 'status', 'description'];
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
            [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); }",
            ],
        ];

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }
}
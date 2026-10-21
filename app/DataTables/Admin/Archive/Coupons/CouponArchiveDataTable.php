<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Archive\Coupons;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Coupon;

class CouponArchiveDataTable extends AbstractDataTable
{
    private string $page = 'admin.archives.coupons';

    private string $route = 'admin.archives.coupons';

    protected function getTableId(): string
    {
        return 'coupons-archive-table';
    }

    protected function resource()
    {
        return Coupon::onlyTrashed();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الكوبون'],
            ['data' => 'code', 'name' => 'code', 'title' => 'الكود'],
            ['data' => 'discount', 'name' => 'discount_value', 'title' => 'قيمة الخصم'],
            ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('name', fn ($row) => '<span class="fw-bold text-heading">'.e($row->name).'</span>')
            ->editColumn('code', fn ($row) => '<span class="badge bg-label-dark font-monospace">'.e($row->code).'</span>')
            ->addColumn('discount', function ($row) {
                return $row->discount_type === 'percentage'
                    ? $row->discount_value.'%'
                    : number_format((float) $row->discount_value, 2).' ر.س';
            })
            ->editColumn('deleted_at', fn ($row) => $row->deleted_at?->format('Y-m-d H:i') ?? '-')
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'code', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'code'];
    }

    protected function getCustomOrder(): array
    {
        return [4, 'desc'];
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

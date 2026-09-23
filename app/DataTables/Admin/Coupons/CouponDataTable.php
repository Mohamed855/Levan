<?php

namespace App\DataTables\Admin\Coupons;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Coupons\Coupon;

class CouponDataTable extends AbstractDataTable
{
    private $page  = "admin.coupons";
    private $route = "admin.coupons";


    // table id
    protected function getTableId()
    {
        return "coupon-table";
    }


    protected function resource()
    {
        return Coupon::query();
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
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'name',     'name' => 'name',   'title' => 'اسم الكوبون',   'className' => 'text-start'],
            ['data' => 'code',     'name' => 'code',   'title' => 'الكود',          'className' => 'text-start'],

            ['data' => 'discount_type',    'name' => 'discount_type',       'title' => 'نوع الخصم',],
            ['data' => 'discount_value',   'name' => 'discount_value',      'title' => 'قيمة الخصم',],
            ['data' => 'max_discount',     'name' => 'max_discount',        'title' => 'الحد الأقصى للخصم',],
            ['data' => 'expiry_date',      'name' => 'expiry_date',         'title' => 'تاريخ الانتهاء',],
            ['data' => 'usage_limit',      'name' => 'usage_limit',         'title' => 'حد الاستخدام',],
            ['data' => 'used_count',      'name' => 'used_count',         'title' => 'مرات الاستخدام',],

            ['data' => 'is_active',         'name' => 'is_active',          'title' => 'الحالة',],
            ['data' => 'actions',           'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }




    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('code', function ($row) {
                return $this->copyableCode($row);
            })


            ->editColumn('discount_type', function ($row) {
                return $this->DiscountTypeBadge($row);
            })

            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">مفعل</span>'
                    : '<span class="badge bg-danger">غير مفعل</span>';
            })

            ->editColumn('expiry_date', function ($row) {
                return $row->expiry_date?->format('Y-m-d');
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'code', 'discount_type', 'is_active', 'status', 'actions'];
    }
    
     protected function applyCustomFilters($query)
    {
        $request = request();
   
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }
        return $query;
    }


    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'name',
        ];
    }


    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }


    // Buttons
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل' , 'exportOptions' => [
                    'columns' => [11, 10, 9, 8, 7, 6, 5, 4, 3]
                ]],
        ];

        if (auth()->user()?->can('حذف كوبون')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); }",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        if (auth()->user()?->can('إضافة كوبون')) {
            return [
                'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة كوبون',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
            ];
        } else {
            return [];
        }
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function copyableCode($row): string
    {
        return sprintf(
            '<div class="d-flex align-items-center gap-2">
                <code class="copyable-code"
                      data-code="%s"
                      style="cursor: pointer; user-select: none; padding: 4px 8px; background: #f5f5f5; border-radius: 4px; font-weight: 600;"
                      title="اضغط للنسخ">
                    %s
                </code>
                <i class="ti ti-copy text-muted" style="font-size: 16px;"></i>
            </div>',
            $row->code,
            $row->code
        );
    }


    private function DiscountTypeBadge($row): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $row->discount_type->color(),
            $row->discount_type->label()
        );
    }
}

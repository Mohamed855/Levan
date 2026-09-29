<?php

namespace App\DataTables\Admin\Plans;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Plans\Plan;
use App\Models\BusinessCenter\Company\Company;
use Carbon\Carbon;

class PlanDataTable extends AbstractDataTable
{
    private $page  = "admin.plans";
    private $route = "admin.plans";


    // table id
    protected function getTableId()
    {
        return "paln-table";
    }


    protected function resource()
    {
        return Plan::query();
    }


    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'checkbox',  'name' => 'checkbox',   'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'sort_order', 'name' => 'sort_order', 'title' => 'الترتيب', 'width' => '80px'],
            ['data' => 'name',               'name' => 'name',                      'title' => 'اسم الباقة',    'className' => 'text-start'],
            ['data' => 'price',              'name' => 'price',                     'title' => 'السعر ',],
            ['data' => 'discount_percentage', 'name' => 'discount_percentage',       'title' => 'نسبة الخصم'],
            ['data' => 'price_after_discount', 'name' => 'price',                     'title' => 'السعر بعد الخصم'],

            ['data' => 'yearly_renewal_price', 'name' => 'yearly_renewal_price',                     'title' => 'سعر التجديد'],

            ['data' => 'included_users',     'name' => 'included_users',            'title' => 'عدد المستخدمين',],
            ['data' => 'extra_user_license_price',     'name' => 'extra_user_license_price',            'title' => 'سعر الترخيص الاضافي',],
            ['data' => 'branches_info',      'name' => 'branches_limit',            'title' => 'عدد الفروع'],
            ['data' => 'domain_management',  'name' => 'domain_management_enabled', 'title' => 'إعدادات الدومينات'],
            ['data' => 'is_active',          'name' => 'is_active',                 'title' => 'الحالة',],
            ['data' => 'actions',            'name' => 'actions',                   'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }


    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')

            ->editColumn('sort_order', function ($row) {
                return '<span class="badge bg-label-info cursor-move"><i class="ti ti-hand-move"></i> </span>';
            })

            ->editColumn('price', function ($row) {
                return number_format($row->price);
            })

            ->editColumn('discount_percentage', function ($row) {
                if (is_null($row->discount_percentage) || $row->discount_percentage == 0) {
                    return '-';
                }

                return $row->discount_percentage . ' %';
            })

            ->addColumn('price_after_discount', function ($row) {
                $price = (float) $row->price;
                $discount = (float) ($row->discount_percentage ?? 0);

                if ($discount > 0) {
                    $discountAmount = ($price * $discount) / 100;
                    $finalPrice = $price - $discountAmount;
                } else {
                    $finalPrice = $price;
                }

                return number_format($finalPrice);
            })

            ->editColumn('yearly_renewal_price', function ($row) {
                return number_format($row->yearly_renewal_price);
            })

            ->addColumn('subscription_duration', function ($row) {
                return $row->subscription_duration_type === 'unlimited' ? 'غير محددة' : ($row->subscription_billing_cycle === 'monthly' ? 'محددة - شهرية' : 'محددة - سنوية');
            })

            ->addColumn('branches_info', function ($row) {
                if ($row->unlimited_branches) {
                    return '<span class="badge bg-label-primary">غير محدود</span>';
                }

                return $row->branches_limit ? e($row->branches_limit) : '-';
            })

            ->addColumn('domain_management', function ($row) {
                if ($row->domain_management_enabled) {
                    return '<span class="badge bg-success">مفعّلة</span>';
                }

                return '<span class="badge bg-secondary">غير مفعّلة</span>';
            })


            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">مفعل</span>'
                    : '<span class="badge bg-danger">غير مفعل</span>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'sort_order', 'name', 'branches_info', 'domain_management', 'is_active', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->filled('domain_management_enabled')) {
            $query->where('domain_management_enabled', $request->domain_management_enabled);
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
        return [2, 'desc'];
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
            ['extend' => 'excel', 'text' => 'إكسل',  'exportOptions' => [
                'columns' => [12, 11, 10, 9, 8, 7, 6, 5, 4, 3]
            ]],
        ];

        if (auth()->user()?->can('plans.delete')) {
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
        if (auth()->user()?->can('plans.create')) {
            return [
                'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة باقة',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
            ];
        } else {
            return [];
        }
    }
}

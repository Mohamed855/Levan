<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Coupons;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Models\Admin\Coupon;

class CouponDataTable extends AbstractDataTable
{
    private string $page = 'admin.coupons';

    private string $route = 'admin.coupons';

    protected function getTableId(): string
    {
        return 'coupon-table';
    }

    protected function resource()
    {
        return Coupon::query();
    }

    protected function getColumns(): array
    {
        return [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
            ],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الكوبون', 'className' => 'text-start'],
            ['data' => 'code', 'name' => 'code', 'title' => 'الكود', 'className' => 'text-start'],
            ['data' => 'discount_type', 'name' => 'discount_type', 'title' => 'نوع الخصم'],
            ['data' => 'discount_value', 'name' => 'discount_value', 'title' => 'قيمة الخصم'],
            ['data' => 'max_discount', 'name' => 'max_discount', 'title' => 'الحد الأقصى للخصم'],
            ['data' => 'expires_at', 'name' => 'expires_at', 'title' => 'تاريخ الانتهاء'],
            ['data' => 'usage_limit', 'name' => 'usage_limit', 'title' => 'حد الاستخدام'],
            ['data' => 'used_count', 'name' => 'used_count', 'title' => 'مرات الاستخدام'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

            ->editColumn('code', function ($row) {
                $code = htmlspecialchars((string) $row->code, ENT_QUOTES, 'UTF-8');

                return '<div class="d-inline-flex align-items-center gap-2 copyable-code-wrapper" data-code="'.$code.'" title="اضغط لنسخ الكود">'
                    .'<code class="copyable-code" data-code="'.$code.'">'.$code.'</code>'
                    .'<i class="ti ti-copy text-muted copy-icon"></i>'
                    .'</div>';
            })

            ->editColumn('discount_type', function ($row) {
                $type = $row->discount_type instanceof CouponDiscountType
                    ? $row->discount_type
                    : CouponDiscountType::tryFrom((string) $row->discount_type);

                $label = $type ? $type->label() : ($row->discount_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت');
                $color = $type ? $type->color() : ($row->discount_type === 'percentage' ? 'success' : 'primary');

                return '<span class="badge bg-label-'.e($color).'">'.e($label).'</span>';
            })

            ->editColumn('discount_value', function ($row) {
                if ($row->discount_type === 'percentage' || $row->discount_type === CouponDiscountType::PERCENTAGE) {
                    return (float) $row->discount_value.' %';
                }

                return number_format((float) $row->discount_value, 2).' ر.س';
            })

            ->editColumn('max_discount', function ($row) {
                return $row->max_discount ? number_format((float) $row->max_discount, 2).' ر.س' : '<span class="text-muted">-</span>';
            })

            ->editColumn('expires_at', function ($row) {
                return $row->expires_at ? $row->expires_at->format('Y-m-d') : '<span class="text-muted">دائم</span>';
            })

            ->editColumn('usage_limit', function ($row) {
                return $row->usage_limit ? (string) $row->usage_limit : '<span class="text-primary">∞</span>';
            })

            ->editColumn('is_active', function ($row) {
                $user = auth('admin')->user() ?? auth()->user();
                if (! $user?->can('coupons.edit') && ! $user?->can('coupons.update')) {
                    return $row->is_active
                        ? '<span class="badge bg-success">مفعل</span>'
                        : '<span class="badge bg-danger">غير مفعل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.coupons.toggle-status', $row->id);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox"
                                   class="form-check-input js-table-status-toggle"
                                   id="status_'.$row->id.'"
                                   data-url="'.e($url).'"
                                   '.$checked.'
                                   aria-label="تبديل حالة الكوبون">
                        </div>
                    </div>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'code', 'discount_type', 'max_discount', 'expires_at', 'usage_limit', 'is_active', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('is_active')) {
            $val = $request->is_active;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('is_active', 1);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('is_active', 0);
            }
        }

        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return ['name', 'code'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

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
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('coupons.delete')) {
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

    private function getAddButton(): array
    {
        if (auth()->user()?->can('coupons.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة كوبون',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}

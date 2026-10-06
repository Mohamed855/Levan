<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Plans;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Models\Admin\Plans\Plan;
use App\Services\Admin\Plans\PlanService;
use Carbon\Carbon;

class PlanDataTable extends AbstractDataTable
{
    private string $page  = 'admin.plans';
    private string $route = 'admin.plans';

    protected function getTableId(): string
    {
        return 'paln-table';
    }

    protected function resource()
    {
        return Plan::query();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'sort_order', 'name' => 'sort_order', 'title' => 'الترتيب', 'width' => '70px', 'className' => 'text-center plan-sort-order'],
            ['data' => 'code', 'name' => 'code', 'title' => 'الكود', 'className' => 'text-center'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الباقة', 'className' => 'text-start'],
            ['data' => 'plan_type', 'name' => 'plan_type', 'title' => 'النوع', 'className' => 'text-center'],
            ['data' => 'subscription_billing_cycle', 'name' => 'subscription_billing_cycle', 'title' => 'دورة الفوترة', 'className' => 'text-center'],
            ['data' => 'price', 'name' => 'price', 'title' => 'السعر'],
            ['data' => 'discount_percentage', 'name' => 'discount_percentage', 'title' => 'الخصم', 'className' => 'text-center'],
            ['data' => 'price_after_discount', 'name' => 'price', 'title' => 'السعر النهائي'],
            ['data' => 'yearly_renewal_price', 'name' => 'yearly_renewal_price', 'title' => 'العرض السنوي'],
            ['data' => 'limits_mode', 'name' => 'limits_mode', 'title' => 'الحدود', 'className' => 'text-center'],
            ['data' => 'included_users', 'name' => 'included_users', 'title' => 'المستخدمين', 'className' => 'text-center'],
            ['data' => 'properties_limit', 'name' => 'properties_limit', 'title' => 'العقارات', 'className' => 'text-center'],
            ['data' => 'storage_quota_gb', 'name' => 'storage_quota_gb', 'title' => 'التخزين', 'className' => 'text-center'],
            ['data' => 'active_auctions_limit', 'name' => 'active_auctions_limit', 'title' => 'المزادات', 'className' => 'text-center'],
            ['data' => 'available_from', 'name' => 'available_from', 'title' => 'الإتاحة', 'className' => 'text-center'],
            ['data' => 'domain_management', 'name' => 'domain_management_enabled', 'title' => 'الدومينات', 'className' => 'text-center'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $mostRequestedPlanId = app(PlanService::class)->getMostRequestedPlanId();

        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')

            ->editColumn('sort_order', function ($row) {
                return '<span class="badge bg-label-info cursor-move" title="اسحب لإعادة الترتيب"><i class="ti ti-arrows-sort"></i> ' . (int) $row->sort_order . '</span>';
            })

            ->editColumn('code', function ($row) {
                return '<span class="badge bg-label-dark font-monospace fw-bold">' . e($row->code ?? '-') . '</span>';
            })

            ->editColumn('name', function ($row) use ($mostRequestedPlanId) {
                $colorHex = $row->color instanceof PlanColor ? $row->color->hex() : '#7367f0';
                $badgeHtml = '';

                if ($mostRequestedPlanId !== null && (int) $row->id === (int) $mostRequestedPlanId) {
                    $badgeHtml = ' <span class="badge bg-gradient-danger text-white rounded-pill ms-1 fs-tiny shadow-sm"><i class="ti ti-flame me-1"></i>الأكثر طلباً</span>';
                }

                $colorDot = '<span class="badge rounded-circle p-1 me-1" style="background-color: ' . e($colorHex) . ';" title="' . e($row->color?->label() ?? '') . '">&nbsp;</span>';

                return '<div class="d-flex align-items-center">' . $colorDot . '<span class="fw-bold text-heading">' . e($row->name) . '</span>' . $badgeHtml . '</div>';
            })

            ->editColumn('plan_type', function ($row) {
                $type = $row->plan_type instanceof PlanType ? $row->plan_type : PlanType::tryFrom((string) $row->plan_type);
                $label = $type ? $type->label() : (string) $row->plan_type;
                $color = $type ? $type->color() : 'secondary';

                return '<span class="badge bg-label-' . e($color) . '">' . e($label) . '</span>';
            })

            ->editColumn('subscription_billing_cycle', function ($row) {
                $cycle = $row->subscription_billing_cycle instanceof SubscriptionBillingCycle
                    ? $row->subscription_billing_cycle
                    : SubscriptionBillingCycle::tryFrom((string) $row->subscription_billing_cycle);

                $label = $cycle ? $cycle->label() : (string) $row->subscription_billing_cycle;
                $color = $cycle ? $cycle->color() : 'info';

                return '<span class="badge bg-label-' . e($color) . '">' . e($label) . '</span>';
            })

            ->editColumn('price', function ($row) {
                return '<span class="fw-semibold">' . number_format((float) $row->price, 2) . ' ر.س</span>';
            })

            ->editColumn('discount_percentage', function ($row) {
                if (empty($row->discount_percentage) || (float) $row->discount_percentage <= 0) {
                    return '<span class="text-muted">-</span>';
                }

                return '<span class="badge bg-label-success">' . (float) $row->discount_percentage . ' %</span>';
            })

            ->addColumn('price_after_discount', function ($row) {
                return '<span class="fw-bold text-success">' . number_format($row->price_after_discount, 2) . ' ر.س</span>';
            })

            ->editColumn('yearly_renewal_price', function ($row) {
                if (empty($row->yearly_renewal_price) || (float) $row->yearly_renewal_price <= 0) {
                    return '<span class="text-muted">-</span>';
                }

                return '<span class="fw-semibold text-primary">' . number_format((float) $row->yearly_renewal_price, 2) . ' ر.س</span>';
            })

            ->editColumn('limits_mode', function ($row) {
                if ($row->isUnlimited()) {
                    return '<span class="badge bg-label-primary fw-bold"><i class="ti ti-infinity me-1"></i>غير محدود</span>';
                }

                return '<span class="badge bg-label-info">محدود</span>';
            })

            ->editColumn('included_users', function ($row) {
                if ($row->isUnlimited()) {
                    return '<span class="text-primary fw-bold">∞</span>';
                }

                return $row->included_users ? (string) $row->included_users : '-';
            })

            ->editColumn('properties_limit', function ($row) {
                if ($row->isUnlimited()) {
                    return '<span class="text-primary fw-bold">∞</span>';
                }

                return $row->properties_limit ? (string) $row->properties_limit : '-';
            })

            ->editColumn('storage_quota_gb', function ($row) {
                if ($row->isUnlimited()) {
                    return '<span class="text-primary fw-bold">∞</span>';
                }

                return $row->storage_quota_gb ? $row->storage_quota_gb . ' GB' : '-';
            })

            ->editColumn('active_auctions_limit', function ($row) {
                if ($row->isUnlimited()) {
                    return '<span class="text-primary fw-bold">∞</span>';
                }

                return $row->active_auctions_limit ? (string) $row->active_auctions_limit : '-';
            })

            ->editColumn('available_from', function ($row) {
                if (!$row->available_from) {
                    return '<span class="badge bg-label-success">متاحة فوراً</span>';
                }

                if ($row->available_from->isFuture()) {
                    return '<span class="badge bg-label-warning" title="' . e($row->available_from->format('Y-m-d H:i')) . '">مجدولة ' . e($row->available_from->format('Y-m-d')) . '</span>';
                }

                return '<span class="badge bg-label-info">' . e($row->available_from->format('Y-m-d')) . '</span>';
            })

            ->addColumn('domain_management', function ($row) {
                if ($row->domain_management_enabled) {
                    return '<span class="badge bg-label-success">مفعّلة</span>';
                }

                return '<span class="badge bg-secondary">غير مفعّلة</span>';
            })

            ->editColumn('is_active', function ($row) {
                if (!auth()->user()?->can('plans.edit')) {
                    return $row->is_active
                        ? '<span class="badge bg-success">مفعل</span>'
                        : '<span class="badge bg-danger">غير مفعل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.plans.toggle-active', $row);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="' . e($url) . '" ' . $checked . ' aria-label="تبديل حالة الباقة">
                        </div>
                    </div>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', [
                    'row'   => $row,
                    'route' => $this->route,
                ])->render();
            });
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'sort_order',
            'code',
            'name',
            'plan_type',
            'subscription_billing_cycle',
            'price',
            'discount_percentage',
            'price_after_discount',
            'yearly_renewal_price',
            'limits_mode',
            'included_users',
            'properties_limit',
            'storage_quota_gb',
            'active_auctions_limit',
            'available_from',
            'domain_management',
            'is_active',
            'actions',
        ];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('is_active')) {
            $val = $request->is_active;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('is_active', true);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('plan_type')) {
            $query->where('plan_type', $request->plan_type);
        }

        if ($request->filled('subscription_billing_cycle')) {
            $query->where('subscription_billing_cycle', $request->subscription_billing_cycle);
        }

        if ($request->filled('limits_mode')) {
            $query->where('limits_mode', $request->limits_mode);
        }

        if ($request->filled('domain_management_enabled')) {
            $val = $request->domain_management_enabled;
            if ($val === 'active' || $val === '1') {
                $query->where('domain_management_enabled', true);
            } elseif ($val === 'inactive' || $val === '0') {
                $query->where('domain_management_enabled', false);
            }
        }

        if ($request->filled('availability')) {
            if ($request->availability === 'available') {
                $query->where(fn($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', now()));
            } elseif ($request->availability === 'future') {
                $query->where('available_from', '>', now());
            }
        }

        if ($request->filled('discounts')) {
            if ($request->discounts === 'with_discount') {
                $query->where('discount_percentage', '>', 0);
            } elseif ($request->discounts === 'without_discount') {
                $query->where(fn($q) => $q->whereNull('discount_percentage')->orWhere('discount_percentage', '<=', 0));
            }
        }

        if ($request->filled('most_requested') && $request->most_requested === 'yes') {
            $mostRequestedId = app(PlanService::class)->getMostRequestedPlanId();
            if ($mostRequestedId) {
                $query->where('id', $mostRequestedId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return [
            'name',
            'code',
        ];
    }

    protected function getCustomOrder(): array
    {
        return [2, 'asc']; // Sort by sort_order
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

        if (auth()->user()?->can('plans.delete')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => 'function() { handleMassDelete(); }',
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
                'text'      => '<i class="ti ti-plus me-1"></i> إضافة باقة',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
            ];
        }

        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Plans;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanType;
use App\Models\Admin\Plans\Plan;
use App\Services\Admin\Plans\PlanService;

class PlanDataTable extends AbstractDataTable
{
    private string $page = 'admin.plans';

    private string $route = 'admin.plans';

    protected function getTableId(): string
    {
        return 'plans-table';
    }

    protected function resource()
    {
        return Plan::query()
            ->notTrial()
            ->withCount('subscriptions');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'color', 'name' => 'color', 'title' => 'اللون', 'width' => '50px', 'className' => 'text-center align-middle', 'orderable' => true],
            ['data' => 'code', 'name' => 'code', 'title' => 'الكود', 'className' => 'text-center align-middle'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الباقة', 'className' => 'text-center align-middle'],
            ['data' => 'is_most_requested', 'name' => 'is_most_requested', 'title' => 'الأكثر طلباً', 'width' => '95px', 'className' => 'text-center align-middle', 'orderable' => false, 'searchable' => false],
            ['data' => 'plan_type', 'name' => 'plan_type', 'title' => 'النوع', 'className' => 'text-center align-middle'],
            ['data' => 'price', 'name' => 'price', 'title' => 'السعر الشهري', 'className' => 'text-center align-middle'],
            ['data' => 'yearly_price', 'name' => 'yearly_price', 'title' => 'السعر السنوي', 'className' => 'text-center align-middle'],
            ['data' => 'included_users', 'name' => 'included_users', 'title' => 'عدد المستخدمين', 'className' => 'text-center align-middle'],
            ['data' => 'extra_user_license_price', 'name' => 'extra_user_license_price', 'title' => 'سعر المستخدم الإضافي', 'className' => 'text-center align-middle'],
            ['data' => 'limits_summary', 'name' => 'limits_mode', 'title' => 'الحدود التشغيلية', 'className' => 'text-center align-middle'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'التفعيل', 'className' => 'text-center align-middle', 'width' => '90px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px', 'className' => 'text-center align-middle'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $mostRequestedPlanId = app(PlanService::class)->getMostRequestedPlanId();

        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

            ->addColumn('color', function ($row) {
                $planColor = $row->color instanceof PlanColor
                    ? $row->color
                    : ($row->color ? PlanColor::tryFrom((string) $row->color) : null);

                $colorHex = $planColor ? $planColor->hex() : '#7367f0';
                $colorLabel = $planColor ? $planColor->label() : ($row->color ?? '');

                return '<span class="d-inline-block rounded shadow-xs" style="background-color: '.e($colorHex).'; width: 22px; height: 22px; border: 1px solid rgba(0,0,0,0.08); vertical-align: middle;" title="'.e($colorLabel).'" data-bs-toggle="tooltip"></span>';
            })

            ->editColumn('code', fn ($row) => $this->copyableCode($row))

            ->editColumn('name', function ($row) {
                $futureBadge = '';
                if ($row->available_from && $row->available_from->isFuture()) {
                    $futureBadge = '<span class="badge bg-label-warning mt-1 d-inline-flex align-items-center"><i class="ti ti-clock me-1"></i>مجدولة لـ '.$this->formatDate($row->available_from).'</span>';
                }

                return '<div class="d-flex flex-column align-items-center justify-content-center text-center">
                    <span class="text-heading fw-semibold fs-6">'.e($row->name).'</span>
                    '.$futureBadge.'
                </div>';
            })

            ->addColumn('is_most_requested', function ($row) use ($mostRequestedPlanId) {
                $isMostRequested = ($mostRequestedPlanId !== null && (int) $row->id === (int) $mostRequestedPlanId);

                return $isMostRequested
                    ? '<span class="badge bg-success">نعم</span>'
                    : '<span class="badge bg-danger">لا</span>';
            })

            ->editColumn('plan_type', function ($row) {
                $type = $row->plan_type instanceof PlanType ? $row->plan_type : PlanType::tryFrom((string) $row->plan_type);
                $label = $type ? $type->label() : (string) $row->plan_type;
                $color = $type ? $type->color() : 'secondary';

                return '<span class="badge bg-'.e($color).'">'.e($label).'</span>';
            })

            ->editColumn('price', function ($row) {
                $finalPrice = number_format($row->price_after_discount, 2);
                $originalPrice = number_format((float) $row->price, 2);
                $hasDiscount = (float) ($row->discount_percentage ?? 0) > 0;

                $priceHtml = '<div class="d-flex flex-column align-items-center">
                    <div>
                        <span class="fw-bold text-heading">'.$finalPrice.'</span> <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span>';
                if ($hasDiscount) {
                    $priceHtml .= ' <del class="text-muted small ms-1">'.$originalPrice.'</del>';
                }
                $priceHtml .= '</div>';
                if ($hasDiscount) {
                    $priceHtml .= '<span class="badge bg-label-danger mt-1 small">خصم '.(float) $row->discount_percentage.'%</span>';
                }
                $priceHtml .= '</div>';

                return $priceHtml;
            })

            ->addColumn('yearly_price', function ($row) {
                if ($row->yearly_price === null) {
                    return '-';
                }

                $finalYearly = number_format($row->yearly_price_after_discount, 2);
                $originalYearly = number_format((float) $row->yearly_price, 2);
                $hasDiscount = (float) ($row->discount_percentage ?? 0) > 0;

                $html = '<div class="d-flex flex-column align-items-center">
                    <div>
                        <span class="fw-bold text-heading">'.$finalYearly.'</span> <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span>';
                if ($hasDiscount) {
                    $html .= ' <del class="text-muted small ms-1">'.$originalYearly.'</del>';
                }
                $html .= '</div>';
                if ($hasDiscount) {
                    $html .= '<span class="badge bg-label-danger mt-1 small">خصم '.(float) $row->discount_percentage.'%</span>';
                }
                $html .= '</div>';

                return $html;
            })

            ->editColumn('included_users', function ($row) {
                if ($row->hasUnlimitedUsers()) {
                    return '<span class="badge bg-label-success">غير محدود</span>';
                }

                $users = (string) ($row->included_users ?? 1);

                return '<span class="badge bg-label-secondary d-inline-flex align-items-center gap-1 py-1 px-2">
                    <i class="ti ti-users"></i>
                    <span>'.e($users).' مستخدم</span>
                </span>';
            })

            ->editColumn('extra_user_license_price', function ($row) {
                if ($row->hasUnlimitedUsers()) {
                    return '<span class="text-muted">-</span>';
                }

                $extraPrice = (float) ($row->extra_user_license_price ?? 0);
                if ($extraPrice > 0) {
                    return '<span class="fw-semibold text-heading">'.number_format($extraPrice, 2).' <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span></span>';
                }

                return '<span class="text-muted">-</span>';
            })

            ->addColumn('limits_summary', function ($row) {
                if ($row->isUnlimited()) {
                    return '<span class="badge bg-label-success">غير محدودة</span>';
                }

                $properties = $row->properties_limit !== null ? (string) $row->properties_limit : '-';
                $auctions = $row->active_auctions_limit !== null ? (string) $row->active_auctions_limit : '-';
                $storage = $row->storage_quota_gb !== null ? $row->storage_quota_gb.' GB' : '-';

                return '<div class="d-inline-flex align-items-center justify-content-center gap-1 text-nowrap">
                    <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1 py-1 px-2" title="الحد الأقصى للعقارات">
                        <i class="ti ti-building"></i>
                        <span>'.e($properties).' عقار</span>
                    </span>
                    <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1 py-1 px-2" title="الحد الأقصى للمزادات النشطة">
                        <i class="ti ti-gavel"></i>
                        <span>'.e($auctions).' مزاد</span>
                    </span>
                    <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1 py-1 px-2" title="مساحة التخزين">
                        <i class="ti ti-server"></i>
                        <span>'.e($storage).'</span>
                    </span>
                </div>';
            })

            ->editColumn('is_active', function ($row) {
                if (! auth()->user()?->can('plans.edit')) {
                    return $row->is_active
                        ? '<span class="badge bg-success">مفعل</span>'
                        : '<span class="badge bg-danger">غير مفعل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.plans.toggle-active', $row);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة الباقة">
                        </div>
                    </div>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', [
                    'row' => $row,
                    'route' => $this->route,
                ])->render();
            });
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'color',
            'code',
            'name',
            'is_most_requested',
            'plan_type',
            'price',
            'yearly_price',
            'included_users',
            'extra_user_license_price',
            'limits_summary',
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

        if ($request->filled('limits_mode')) {
            $query->where('limits_mode', $request->limits_mode);
        }

        if ($request->filled('availability')) {
            if ($request->availability === 'available') {
                $query->where(fn ($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', now()));
            } elseif ($request->availability === 'future') {
                $query->where('available_from', '>', now());
            }
        }

        if ($request->filled('discounts')) {
            if ($request->discounts === 'with_discount') {
                $query->where('discount_percentage', '>', 0);
            } elseif ($request->discounts === 'without_discount') {
                $query->where(fn ($q) => $q->whereNull('discount_percentage')->orWhere('discount_percentage', '<=', 0));
            }
        }

        if ($request->filled('most_requested')) {
            $mostRequestedId = app(PlanService::class)->getMostRequestedPlanId();
            if ($request->most_requested === 'yes') {
                if ($mostRequestedId) {
                    $query->where('id', $mostRequestedId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif ($request->most_requested === 'no') {
                if ($mostRequestedId) {
                    $query->where('id', '!=', $mostRequestedId);
                }
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
        return [2, 'asc']; // Sort by code
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
        if (auth()->user()?->can('plans.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة باقة',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}

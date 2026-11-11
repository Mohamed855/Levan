<?php

namespace App\DataTables\Admin\SubscriptionMangment;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Services\Shared\Subscription\SubscriptionManager;

class subscriptionMangmentDataTable extends AbstractDataTable
{
    private $page = 'admin.subscription-management';

    // table id
    protected function getTableId()
    {
        return 'subscriptions-table';
    }

    protected function resource()
    {
        return Subscription::query()
            ->select('subscriptions.*')
            ->with([
                'plan' => fn ($q) => $q->withTrashed(),
                'currentPeriod',
                'periods',
                'tenant' => fn ($q) => $q->withTrashed()->with('city'),
            ]);
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
            ['data' => 'subscription_number', 'name' => 'subscription_number', 'title' => 'رقم الاشتراك'],
            ['data' => 'company_name',    'name' => 'tenant.company_name',     'title' => 'اسم الشركة'],
            ['data' => 'city', 'name' => 'tenant.city.name', 'title' => 'المدينة', 'orderable' => false],
            ['data' => 'tax_number', 'name' => 'tenant.tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'tenant_id',       'name' => 'tenant_id',        'title' => 'معرف العميل'],

            ['data' => 'plan_id',         'name' => 'plan.name',          'title' => 'الباقة'],
            ['data' => 'total_users',     'name' => 'total_users',      'title' => 'عدد المستخدمين '],
            ['data' => 'used_users',      'name' => 'used_users',       'title' => 'التراخيص المستخدمة'],
            ['data' => 'starts_at',       'name' => 'starts_at',        'title' => 'بداية اول اشتراك'],
            ['data' => 'period_end',      'name' => 'period_end',       'title' => 'نهاية الإشتراك الحالي', 'orderable' => false, 'searchable' => false],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'نهاية الاشتراك'],

            ['data' => 'days_remaining',    'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => true],
            ['data' => 'created_at',        'name' => 'created_at',     'title' => 'تاريخ الإنشاء', 'className' => 'text-center', 'width' => '130px'],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة الإشتراك'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('subscription_number', function ($row) {
                return '<span class="fw-bold text-primary">'.e($row->subscription_number).'</span>';
            })

            ->editColumn('company_name', function ($row) {
                return $row->tenant?->company_name ?? '-';
            })

            ->addColumn('city', fn ($row) => $row->tenant?->city?->name ?? '-')
            ->addColumn('tax_number', fn ($row) => $row->tenant?->tax_number ?? '-')
            ->editColumn('tenant_id', function ($row) {
                if (! $row->tenant_id) {
                    return '-';
                }
                $dashboardUrl = route('path.dashboard', ['tenant' => $row->tenant_id]);

                return '<a href="'.e($dashboardUrl).'" class="text-dark d-inline-block fw-semibold" title="رابط لوحة التحكم" target="_blank">'
                    .'<i class="ti ti-external-link me-1 text-primary"></i>'
                    .e($row->tenant_id)
                    .'</a>';
            })

            ->editColumn('plan_id', function ($row) {
                return $row->plan?->name ?? '-';
            })

            ->editColumn('starts_at', function ($row) {
                return $this->formatDate($row->starts_at);
            })

            ->editColumn('ends_at', function ($row) {
                return $this->formatDate($row->ends_at ?? $row->currentPeriod?->period_end);
            })
            ->editColumn('period_end', function ($row) {
                $latestPeriod = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();

                return $this->formatDate($latestPeriod?->period_end ?? $row->ends_at);
            })

            // ترتيب عمود الأيام المتبقية
            ->orderColumn('days_remaining', function ($query, $order) {
                $direction = strtolower((string) $order) === 'desc' ? 'desc' : 'asc';
                $expr = 'COALESCE((SELECT sp.period_end FROM subscription_periods sp WHERE sp.subscription_id = subscriptions.id AND sp.deleted_at IS NULL ORDER BY sp.is_current DESC, sp.period_end DESC LIMIT 1), subscriptions.ends_at, subscriptions.trial_end_date)';
                $query->orderByRaw("({$expr} IS NULL), {$expr} {$direction}");
            })

            // عمود الأيام المتبقية
            ->addColumn('days_remaining', function ($row) {
                $isExpired = $this->isExpiredRoute() || in_array($row->status, [SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED], true);
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();
                $service = app(SubscriptionManager::class);
                $days = $service->calculateDaysRemaining($period, $row->ends_at ?? $row->trial_end_date);

                if ($isExpired || ($days !== null && $days <= 0)) {
                    return '<span class="badge bg-danger fw-bold">0 يوم</span>';
                }

                if ($days === null) {
                    return '<span class="badge bg-secondary">-</span>';
                }

                $days = max(0, $days);

                if ($days === 0) {
                    return '<span class="badge bg-danger fw-bold">0 يوم</span>';
                }

                if ($days <= 7) {
                    return '<span class="badge bg-danger fw-bold">'.$days.' '.($days == 1 ? 'يوم' : ($days == 2 ? 'يومان' : 'أيام')).'</span>';
                }

                if ($days <= 30) {
                    return '<span class="badge bg-warning fw-semibold">'.$days.' يوماً</span>';
                }

                return '<span class="badge bg-success">'.$days.' يوماً</span>';
            })

            ->editColumn('created_at', fn ($row): string => $this->formatDate($row->created_at))

            ->editColumn('status', function ($row) {
                return $this->StageBadge($row);
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row])->render();
            });
    }

    private function isExpiringRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.expiring*') || request()->is('*subscription-manage/expiring*') || request()->boolean('expiring_soon');
    }

    private function isExpiredRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.expired*') || request()->is('*subscription-manage/expired*') || request()->boolean('expired');
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($this->isExpiredRoute()) {
            $allowedExpiredStatuses = [
                SubscriptionStatus::EXPIRED->value,
                SubscriptionStatus::CANCELLED->value,
            ];

            if ($request->filled('status') && in_array($request->status, $allowedExpiredStatuses, true)) {
                $query->where('subscriptions.status', $request->status);
            } else {
                $query->whereIn('subscriptions.status', $allowedExpiredStatuses);
            }
        } elseif ($this->isExpiringRoute()) {
            $days = $request->filled('expiring_in') ? (int) $request->expiring_in : 30;
            $query->where('subscriptions.status', SubscriptionStatus::ACTIVE)
                ->where(function ($q) use ($days) {
                    $q->whereHas('currentPeriod', function ($periodQuery) use ($days) {
                        $periodQuery->where('status', SubscriptionPeriodStatus::ACTIVE)
                            ->where('period_end', '<=', now()->addDays($days))
                            ->where('period_end', '>=', now()->startOfDay());
                    })->orWhere(function ($periodQuery) use ($days) {
                        $periodQuery->whereDoesntHave('periods')
                            ->where('subscriptions.ends_at', '<=', now()->addDays($days))
                            ->where('subscriptions.ends_at', '>=', now()->startOfDay());
                    });
                });
        } elseif ($request->filled('status')) {
            $query->where('subscriptions.status', $request->status);
        }

        // Plan filter
        if ($request->filled('plan_id')) {
            $query->where('subscriptions.plan_id', $request->plan_id);
        }

        // City filter (via tenant)
        if ($request->filled('city_id')) {
            $query->whereHas('tenant', function ($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'subscription_number', 'tenant_id', 'days_remaining', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['subscription_number', 'tenant_id', 'tenant.company_name', 'tenant.tax_number'];
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
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            $this->copyActionButton(),
            $this->excelActionButton(),
        ];

        if (auth('admin')->user()?->can('subscriptions.delete')) {
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

    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function StageBadge($row): string
    {
        return sprintf(
            '<div class="d-inline-block text-center">
                <span class="badge bg-%s">%s</span>
            </div>',
            e($row->status->color()),
            e($row->status->label()),
        );
    }
}

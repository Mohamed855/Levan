<?php

namespace App\DataTables\Admin\SubscriptionMangment;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Subscription\Subscription;
use App\Services\Shared\Subscription\SubscriptionManager;

class subscriptionMangmentDataTable extends AbstractDataTable
{
    private $page = 'admin.subscription_manage';

    // table id
    protected function getTableId()
    {
        return 'subscriptions-table';
    }

    protected function resource()
    {
        return Subscription::query()->with(['plan', 'currentPeriod', 'tenant.city', 'tenant.version']);
    }

    protected function getColumns(): array
    {
        $columns = [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
            ],
            ['data' => 'company_name',    'name' => 'tenant.company_name',     'title' => 'اسم الشركة'],
            ['data' => 'city', 'name' => 'tenant.city.name', 'title' => 'المدينة', 'orderable' => false],
            ['data' => 'tax_number', 'name' => 'tenant.tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'tenant_id',       'name' => 'tenant_id',        'title' => 'معرف العميل'],
            ['data' => 'version',              'name' => 'tenant.version.version',            'title' => 'رقم الإصدار', 'orderable' => false],

            ['data' => 'plan_id',         'name' => 'plan.name',          'title' => 'الباقة'],
            ['data' => 'total_users',     'name' => 'total_users',      'title' => 'عدد المستخدمين '],
            ['data' => 'used_users',      'name' => 'used_users',       'title' => 'التراخيص المستخدمة'],
            ['data' => 'starts_at',       'name' => 'starts_at',        'title' => 'بداية اول اشتراك'],
            ['data' => 'period_end',      'name' => 'period_end',       'title' => 'نهاية الإشتراك الحالي', 'orderable' => false, 'searchable' => false],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'نهاية الاشتراك'],

            ['data' => 'days_remaining',    'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة الإشتراك'],
        ];

        if ($this->isPendingRoute()) {
            $columns[] = ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'];
        }

        return $columns;
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('company_name', function ($row) {
                return $row->tenant->company_name ?? '-';
            })

            ->addColumn('city', fn ($row) => $row->tenant?->city?->name ?? '-')
            ->addColumn('tax_number', fn ($row) => $row->tenant?->tax_number ?? '-')
            ->editColumn('tenant_id', function ($row) {
                if (!$row->tenant_id) {
                    return '-';
                }
                $dashboardUrl = url('/'.$row->tenant_id.'/dashboard');

                return '<a href="'.$dashboardUrl.'" class="text-dark d-inline-block fw-semibold" title="رابط لوحة التحكم" target="_blank">'
                    .'<i class="ti ti-external-link me-1 text-primary"></i>'
                    .e($row->tenant_id)
                    .'</a>';
            })

            ->editColumn('plan_id', function ($row) {
                return $row->plan->name ?? '-';
            })

            ->editColumn('version', function ($row) {
                return $row->tenant->version?->version;
            })

            ->editColumn('starts_at', function ($row) {
                return $row->starts_at?->format('Y-m-d') ?? '-';
            })

            ->editColumn('ends_at', fn ($row) => $row->ends_at?->format('Y-m-d') ?? '-')
            ->editColumn('period_end', function ($row) {
                $latestPeriod = $row->periods()->latest('period_end')->first();

                return $latestPeriod?->period_end?->format('Y-m-d') ?? '-';
            })

            // عمود الأيام المتبقية
            ->addColumn('days_remaining', function ($row) {
                $period = $row->currentPeriod ?? $row->periods()->latest('period_end')->first();
                $service = app(SubscriptionManager::class);
                $days = $service->calculateDaysRemaining($period);

                if ($days === null) {
                    return '<span class="badge bg-secondary">-</span>';
                }

                if ($days <= 0) {
                    return '<span class="badge bg-label-danger">منتهي (0 يوم)</span>';
                }

                if ($days <= 7) {
                    return '<span class="badge bg-label-danger fw-bold"><i class="ti ti-alert-triangle ti-xs me-1"></i>'.$days.' '.($days == 1 ? 'يوم' : ($days == 2 ? 'يومان' : 'أيام')).'</span>';
                }

                if ($days <= 30) {
                    return '<span class="badge bg-label-warning fw-semibold"><i class="ti ti-clock ti-xs me-1"></i>'.$days.' يوماً</span>';
                }

                return '<span class="badge bg-label-success">'.$days.' يوماً</span>';
            })

            ->editColumn('status', function ($row) {
                return $this->StageBadge($row);
            })

            ->editColumn('price', function ($row) {
                return number_format($row->price, 2).' ر.س';
            });

        if ($this->isPendingRoute()) {
            $datatable->addColumn('actions', function ($row) {
                return view($this->page.'.action', [
                    'row' => $row,
                    'isPending' => $this->isPendingRoute(),
                ])->render();
            });
        }
    }

    private function isExpiringRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.expiring*') || request()->is('*subscription-manage/expiring*') || request()->boolean('expiring_soon');
    }

    private function isExpiredRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.expired*') || request()->is('*subscription-manage/expired*') || request()->boolean('expired');
    }

    private function isPendingRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.pending*') || request()->is('*subscription-manage/pending*');
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($this->isPendingRoute()) {
            $query->where('status', 'pending');
        } elseif ($this->isExpiredRoute()) {
            $query->where('status', 'expired');
        } elseif ($this->isExpiringRoute()) {
            $query->where('status', 'active')
                ->whereHas('currentPeriod', function ($q) {
                    $q->where('status', 'active')
                        ->where('period_end', '<=', now()->addDays(30))
                        ->where('period_end', '>=', now()->startOfDay());
                });
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        $columns = ['checkbox', 'tenant_id', 'plan_id', 'billing_cycle', 'city', 'tax_number', 'days_remaining', 'status'];

        if ($this->isPendingRoute()) {
            $columns[] = 'actions';
        }

        return $columns;
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
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [11, 10, 9, 8, 7, 6, 5, 4, 3],
            ]],
        ];

        if ($this->isPendingRoute()) {
            $buttons[] = [
                'text' => 'قبول المحدد',
                'className' => 'btn btn-default btn-accept-selected',
                'action' => 'function() { handleMassAccept(); }',
            ];
            $buttons[] = [
                'text' => 'رفض المحدد',
                'className' => 'btn btn-default btn-reject-selected',
                'action' => 'function() { handleMassReject(); }',
            ];
        }

        $buttons[] = [
            'text' => 'حذف المحدد',
            'className' => 'btn btn-default btn-delete-selected',
            'action' => 'function() { handleMassDelete(); }',
        ];

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

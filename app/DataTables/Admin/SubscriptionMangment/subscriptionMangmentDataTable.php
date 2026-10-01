<?php

namespace App\DataTables\Admin\SubscriptionMangment;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Subscription\Subscription;
use App\Services\shared\Subscription\SubscriptionManager;

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
        return Subscription::query()->with(['plan', 'currentPeriod', 'tenant.city']);
    }

    protected function getColumns(): array
    {
        $columns = [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'company_name',    'name' => 'company_name',     'title' => 'اسم الشركة'],
            ['data' => 'tenant_status', 'name' => 'tenant_status', 'title' => 'حالة الشركة'],
            ['data' => 'city', 'name' => 'city_id', 'title' => 'المدينة'],
            ['data' => 'tax_number', 'name' => 'tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'tenant_id',       'name' => 'tenant_id',        'title' => 'معرف العميل'],
            ['data' => 'version',              'name' => 'version',            'title' => 'رقم الإصدار'],

            ['data' => 'plan_id',         'name' => 'plan_id',          'title' => 'الباقة'],
            ['data' => 'total_users',     'name' => 'total_users',      'title' => 'عدد المستخدمين '],
            ['data' => 'used_users',      'name' => 'used_users',       'title' => 'التراخيص المستخدمة'],
            ['data' => 'starts_at',       'name' => 'starts_at',        'title' => 'بداية اول اشتراك'],
            ['data' => 'period_end',      'name' => 'period_end',       'title' => 'نهاية الإشتراك الحالي'],
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
            ->addColumn('tenant_status', function ($row) {
                $status = $row->tenant?->status ?? 'pending';
                $labels = ['draft' => 'مسودة', 'pending' => 'قيد المراجعة', 'active' => 'نشطة'];
                $classes = ['draft' => 'secondary', 'pending' => 'warning', 'active' => 'success'];

                return '<span class="badge bg-label-'.($classes[$status] ?? 'secondary').'">'.($labels[$status] ?? $status).'</span>';
            })
            ->editColumn('tenant_id', function ($row) {
                $url = route('admin.companies.tenant-access', ['tenant' => $row->tenant_id]);

                return '<a href="'.$url.'" class="text-dark d-inline-block" title="رابط العميل" target="_blank">'
                    .'<i class="ti ti-external-link me-1 text-primary"></i>'
                    .$row->tenant_id.
                    '</a>';
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
                    return '<span class="badge bg-label-secondary">-</span>';
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
        return request()->routeIs('*subscriptionManage.expiring*') || request()->boolean('expiring_soon');
    }

    private function isExpiredRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.expired*') || request()->boolean('expired');
    }

    private function isPendingRoute(): bool
    {
        return request()->routeIs('*subscriptionManage.pending*');
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
        $columns = ['checkbox', 'tenant_id', 'plan_id', 'billing_cycle', 'city', 'tax_number', 'tenant_status', 'days_remaining', 'status'];

        if ($this->isPendingRoute()) {
            $columns[] = 'actions';
        }

        return $columns;
    }

    protected function getSearchableColumns(): array
    {
        return ['version', 'name', 'stage', 'file_path'];
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

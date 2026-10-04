<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Subscriptions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Services\shared\Subscription\SubscriptionManager;

class SubscriptionDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'subscriptions-table';
    }

    protected function resource()
    {
        $query = Subscription::query()
            ->with(['tenant.city', 'plan', 'currentPeriod', 'periods']);

        // Filter: status
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        // Filter: plan
        if (request()->filled('plan_id')) {
            $query->where('plan_id', request('plan_id'));
        }

        // Filter: company's city
        if (request()->filled('city_id')) {
            $query->whereHas('tenant', function ($q) {
                $q->where('city_id', request('city_id'));
            });
        }

        // Filter: billing cycle
        if (request()->filled('billing_cycle')) {
            $query->whereHas('plan', function ($q) {
                $q->where('subscription_billing_cycle', request('billing_cycle'));
            });
        }

        // Filter: start date & end date
        if (request()->filled('start_from')) {
            $query->whereDate('starts_at', '>=', request('start_from'));
        }
        if (request()->filled('end_to')) {
            $query->whereDate('ends_at', '<=', request('end_to'));
        }

        // Filter: expiring soon (7 or 30 days)
        if (request()->filled('expiring_in')) {
            $days = (int) request('expiring_in');
            $query->whereHas('currentPeriod', function ($q) use ($days) {
                $q->where('period_end', '<=', now()->addDays($days))
                    ->where('period_end', '>=', now()->startOfDay());
            });
        }

        return $query;
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'subscription_number', 'name' => 'subscription_number', 'title' => 'رقم الاشتراك'],
            ['data' => 'company_name', 'name' => 'tenant.company_name', 'title' => 'اسم الشركة'],
            ['data' => 'tax_number', 'name' => 'tenant.tax_number', 'title' => 'الرقم الضريبي'],
            // ['data' => 'tenant_id', 'name' => 'tenant_id', 'title' => 'معرف الشركة'],
            ['data' => 'plan_id', 'name' => 'plan.name', 'title' => 'الباقة'],
            // ['data' => 'plan_limits', 'name' => 'plan_limits', 'title' => 'حدود الباقة', 'orderable' => false, 'searchable' => false],
            ['data' => 'starts_at', 'name' => 'starts_at', 'title' => 'تاريخ البداية'],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'تاريخ النهاية'],
            ['data' => 'days_remaining', 'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة الاشتراك'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('subscription_number', fn (Subscription $row) => '<span class="fw-bold text-primary">'.e($row->subscription_number).'</span>')
            ->addColumn('company_name', fn (Subscription $row) => $row->tenant?->company_name ?: '-')
            ->addColumn('tax_number', fn (Subscription $row) => $row->tenant?->tax_number ?: '-')
            ->editColumn('tenant_id', function (Subscription $row) {
                $dashboardUrl = url('/'.$row->tenant_id.'/dashboard');

                return '<a href="'.$dashboardUrl.'" class="text-dark d-inline-block fw-semibold" title="فتح لوحة التحكم" target="_blank">'
                    .'<i class="ti ti-external-link me-1 text-primary"></i>'
                    .e($row->tenant_id)
                    .'</a>';
            })
            ->editColumn('plan_id', fn (Subscription $row) => $row->plan?->name ?: '-')
            ->addColumn('plan_limits', function (Subscription $row) {
                $plan = $row->plan;
                $users = $row->total_users ?: ($plan?->included_users ?: 1);
                $props = $plan?->properties_limit !== null ? $plan->properties_limit : '∞';
                $storage = $plan?->storage_quota_gb !== null ? ($plan->storage_quota_gb.'GB') : '∞';
                $auctions = $plan?->active_auctions_limit !== null ? $plan->active_auctions_limit : '∞';

                return '<div class="d-flex flex-wrap gap-1">'
                    .'<span class="badge bg-label-info" title="المستخدمون"><i class="ti ti-users ti-xs me-1"></i>'.$users.'</span>'
                    .'<span class="badge bg-secondary" title="العقارات"><i class="ti ti-building ti-xs me-1"></i>'.$props.'</span>'
                    .'<span class="badge bg-secondary" title="المساحة"><i class="ti ti-database ti-xs me-1"></i>'.$storage.'</span>'
                    .'<span class="badge bg-secondary" title="المزادات"><i class="ti ti-gavel ti-xs me-1"></i>'.$auctions.'</span>'
                    .'</div>';
            })
            ->editColumn('starts_at', fn (Subscription $row) => $row->starts_at?->format('Y-m-d') ?: '-')
            ->editColumn('ends_at', function (Subscription $row) {
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();

                return $period?->period_end?->format('Y-m-d') ?: ($row->ends_at?->format('Y-m-d') ?: '-');
            })
            ->addColumn('days_remaining', function (Subscription $row) {
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();
                $days = app(SubscriptionManager::class)->calculateDaysRemaining($period);

                if ($days === null) {
                    return '<span class="badge bg-secondary">-</span>';
                }
                if ($days <= 0) {
                    return '<span class="badge bg-label-danger">منتهي (0 يوم)</span>';
                }
                if ($days <= 7) {
                    return '<span class="badge bg-label-danger fw-bold"><i class="ti ti-alert-triangle ti-xs me-1"></i>'.$days.' '.($days === 1 ? 'يوم' : ($days === 2 ? 'يومان' : 'أيام')).'</span>';
                }
                if ($days <= 30) {
                    return '<span class="badge bg-label-warning fw-semibold"><i class="ti ti-clock ti-xs me-1"></i>'.$days.' يوماً</span>';
                }

                return '<span class="badge bg-label-success">'.$days.' يوماً</span>';
            })
            ->editColumn('status', function (Subscription $row) {
                $isActive = $row->status === SubscriptionStatus::ACTIVE;
                $checked = $isActive ? 'checked' : '';

                if (auth()->user()?->can('subscriptions.toggle_status')) {
                    $url = route('admin.subscriptions.toggle-status', $row->id);

                    return '
                        <div class="d-flex justify-content-center">
                            <div class="form-check form-switch mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input js-table-status-toggle"
                                    id="sub_status_'.$row->id.'"
                                    data-url="'.$url.'"
                                    '.$checked.'
                                    aria-label="تبديل حالة الاشتراك"
                                >
                            </div>
                        </div>
                    ';
                }

                return '<span class="badge bg-'.$row->status->color().'">'.$row->status->label().'</span>';
            })
            ->addColumn('actions', fn (Subscription $row) => view('admin.subscriptions.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['subscription_number', 'tenant_id', 'plan_limits', 'days_remaining', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['subscription_number', 'tenant_id', 'tenant.company_name', 'tenant.tax_number'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => [
                    ['extend' => 'copy', 'text' => 'نسخ'],
                    ['extend' => 'excel', 'text' => 'إكسل'],
                ],
            ],
            [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة اشتراك',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route('admin.subscriptions.create')."'; }",
            ],
        ];
    }
}

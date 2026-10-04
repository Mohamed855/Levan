<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Companies;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Subscription\SubscriptionManager;

class CompanyDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'companies-table';
    }

    protected function resource()
    {
        $query = Tenant::query()
            ->with([
                'city',
                'version',
                'lastSubscription.plan',
                'lastSubscription.currentPeriod',
                'lastSubscription.periods',
            ]);

        // Filter: company status
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        // Filter: city
        if (request()->filled('city_id')) {
            $query->where('city_id', request('city_id'));
        }

        // Filter: version
        if (request()->filled('version_id')) {
            $query->where('version_id', request('version_id'));
        }

        // Filter: plan
        if (request()->filled('plan_id')) {
            $query->whereHas('lastSubscription', function ($q) {
                $q->where('plan_id', request('plan_id'));
            });
        }

        // Filter: last subscription status
        if (request()->filled('subscription_status')) {
            $query->whereHas('lastSubscription', function ($q) {
                $q->where('status', request('subscription_status'));
            });
        }

        // Filter: expiring soon (7 or 30 days)
        if (request()->filled('expiring_in')) {
            $days = (int) request('expiring_in');
            $query->whereHas('lastSubscription.currentPeriod', function ($q) use ($days) {
                $q->where('period_end', '<=', now()->addDays($days))
                    ->where('period_end', '>=', now()->startOfDay());
            });
        }

        // Filter: created_at range
        if (request()->filled('created_from')) {
            $query->whereDate('created_at', '>=', request('created_from'));
        }
        if (request()->filled('created_to')) {
            $query->whereDate('created_at', '<=', request('created_to'));
        }

        return $query;
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'company_name', 'name' => 'company_name', 'title' => 'اسم الشركة'],
            ['data' => 'primary_contact_name', 'name' => 'primary_contact_name', 'title' => 'المسؤول الرئيسي'],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة الشركة'],
            ['data' => 'city', 'name' => 'city_id', 'title' => 'المدينة'],
            ['data' => 'tax_number', 'name' => 'tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'commercial_number', 'name' => 'commercial_number', 'title' => 'السجل التجاري'],
            // ['data' => 'tenant_id', 'name' => 'id', 'title' => 'معرف الشركة'],
            ['data' => 'version', 'name' => 'version_id', 'title' => 'رقم الإصدار'],
            ['data' => 'plan', 'name' => 'plan', 'title' => 'الباقة الحالية', 'orderable' => false, 'searchable' => false],
            ['data' => 'total_users', 'name' => 'total_users', 'title' => 'إجمالي المستخدمين', 'orderable' => false, 'searchable' => false],
            ['data' => 'used_users', 'name' => 'used_users', 'title' => 'المقاعد المستخدمة', 'orderable' => false, 'searchable' => false],
            ['data' => 'starts_at', 'name' => 'starts_at', 'title' => 'بداية آخر اشتراك', 'orderable' => false, 'searchable' => false],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'نهاية آخر اشتراك', 'orderable' => false, 'searchable' => false],
            ['data' => 'days_remaining', 'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false, 'searchable' => false],
            ['data' => 'subscription_status', 'name' => 'subscription_status', 'title' => 'حالة الاشتراك', 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '130px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('company_name', fn (Tenant $row) => $row->company_name ?: '-')
            ->addColumn('primary_contact_name', fn (Tenant $row) => $row->primary_contact_name ?: '-')
            ->addColumn('city', fn (Tenant $row) => $row->city?->name ?: '-')
            ->editColumn('tax_number', fn (Tenant $row) => $row->tax_number ?: '-')
            ->editColumn('commercial_number', fn (Tenant $row) => $row->commercial_number ?: '-')
            ->editColumn('tenant_id', function (Tenant $row) {
                $dashboardUrl = url('/'.$row->id.'/dashboard');

                return '<a href="'.$dashboardUrl.'" class="text-dark d-inline-block fw-semibold" title="فتح لوحة التحكم" target="_blank">'
                    .'<i class="ti ti-external-link me-1 text-primary"></i>'
                    .e($row->id)
                    .'</a>';
            })
            ->addColumn('version', fn (Tenant $row) => $row->version?->version ?: '-')
            ->addColumn('plan', fn (Tenant $row) => $row->lastSubscription?->plan?->name ?: '-')
            ->addColumn('total_users', fn (Tenant $row) => $row->lastSubscription ? ($row->lastSubscription->total_users ?: $row->lastSubscription->plan?->included_users ?: '-') : '-')
            ->addColumn('used_users', fn (Tenant $row) => $row->lastSubscription ? $row->lastSubscription->used_users : '-')
            ->addColumn('starts_at', fn (Tenant $row) => $row->lastSubscription?->starts_at?->format('Y-m-d') ?: '-')
            ->addColumn('ends_at', function (Tenant $row) {
                $sub = $row->lastSubscription;
                if (! $sub) {
                    return '-';
                }
                $period = $sub->currentPeriod ?? $sub->periods->sortByDesc('period_end')->first();

                return $period?->period_end?->format('Y-m-d') ?: ($sub->ends_at?->format('Y-m-d') ?: '-');
            })
            ->addColumn('days_remaining', function (Tenant $row) {
                $sub = $row->lastSubscription;
                if (! $sub) {
                    return '<span class="badge bg-secondary">-</span>';
                }
                $period = $sub->currentPeriod ?? $sub->periods->sortByDesc('period_end')->first();
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
            ->addColumn('subscription_status', function (Tenant $row) {
                $sub = $row->lastSubscription;
                if (! $sub || ! $sub->status) {
                    return '<span class="badge bg-secondary">لا يوجد</span>';
                }

                return '<span class="badge bg-'.$sub->status->color().'">'.$sub->status->label().'</span>';
            })
            ->editColumn('status', function (Tenant $row) {
                $statusMap = [
                    'active' => ['label' => 'نشط', 'color' => 'success'],
                    'pending' => ['label' => 'قيد المراجعة', 'color' => 'warning'],
                    'suspended' => ['label' => 'موقوف', 'color' => 'danger'],
                ];
                $status = $statusMap[$row->status] ?? ['label' => $row->status, 'color' => 'secondary'];

                return '<span class="badge bg-label-'.$status['color'].'">'.$status['label'].'</span>';
            })
            ->addColumn('actions', fn (Tenant $row) => view('admin.companies.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['tenant_id', 'days_remaining', 'subscription_status', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['company_name', 'tax_number', 'commercial_number', 'id', 'primary_contact_name', 'primary_contact_phone', 'primary_contact_email'];
    }

    protected function getCustomOrder(): array
    {
        return [2, 'desc'];
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
            ...(auth()->user()?->can('companies.create') ? [[
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة شركة',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route('admin.companies.create')."'; }",
            ]] : []),
        ];
    }
}

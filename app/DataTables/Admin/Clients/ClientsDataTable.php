<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Clients;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Subscription\SubscriptionManager;

class ClientsDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'clients-table';
    }

    protected function resource()
    {
        $query = Tenant::query()
            ->with([
                'city',
                'lastSubscription.plan',
                'lastSubscription.currentPeriod',
                'lastSubscription.periods',
            ]);

        // Filter: city
        if (request()->filled('city_id')) {
            $query->where('city_id', request('city_id'));
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
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'company_name', 'name' => 'company_name', 'title' => 'اسم العميل / الشركة'],
            ['data' => 'primary_contact_name', 'name' => 'primary_contact_name', 'title' => 'المسؤول الرئيسي'],
            ['data' => 'city', 'name' => 'city_id', 'title' => 'المدينة'],
            ['data' => 'tax_number', 'name' => 'tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'commercial_number', 'name' => 'commercial_number', 'title' => 'السجل التجاري'],
            ['data' => 'plan', 'name' => 'plan', 'title' => 'الباقة الحالية', 'orderable' => false, 'searchable' => false],
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
        $datatable->addColumn('checkbox', fn (Tenant $row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">');

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
            ->addColumn('plan', fn (Tenant $row) => $row->lastSubscription?->plan?->name ?: '-')
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
                    return '<span class="badge bg-danger">منتهي (0 يوم)</span>';
                }
                if ($days <= 7) {
                    return '<span class="badge bg-danger fw-bold">'.$days.' '.($days === 1 ? 'يوم' : ($days === 2 ? 'يومان' : 'أيام')).'</span>';
                }
                if ($days <= 30) {
                    return '<span class="badge bg-warning fw-semibold">'.$days.' يوماً</span>';
                }

                return '<span class="badge bg-success">'.$days.' يوماً</span>';
            })
            ->addColumn('subscription_status', function (Tenant $row) {
                $sub = $row->lastSubscription;
                if (! $sub || ! $sub->status) {
                    return '<span class="badge bg-secondary">لا يوجد</span>';
                }

                return '<span class="badge bg-'.$sub->status->color().'">'.$sub->status->label().'</span>';
            })
            ->addColumn('actions', fn (Tenant $row) => view('admin.clients.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'tenant_id', 'days_remaining', 'subscription_status', 'actions'];
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

        if (auth()->user()?->can('clients.delete') || auth()->user()?->can('companies.delete')) {
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
        if (! auth()->user()?->can('clients.create') && ! auth()->user()?->can('companies.create')) {
            return [];
        }

        return [
            'text' => '<i class="ti ti-plus me-1"></i> إضافة عميل',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ window.location.href='".route('admin.clients.create')."'; }",
        ];
    }
}

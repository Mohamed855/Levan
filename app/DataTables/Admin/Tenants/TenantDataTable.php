<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Tenants;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Tenants\Tenant;

class TenantDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'tenants-table';
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

        if (request()->filled('city_id')) {
            $query->where('city_id', request('city_id'));
        }

        if (request()->filled('plan_id')) {
            $query->whereHas('lastSubscription', function ($q) {
                $q->where('plan_id', request('plan_id'));
            });
        }

        if (request()->filled('subscription_status')) {
            $query->whereHas('lastSubscription', function ($q) {
                $q->where('status', request('subscription_status'));
            });
        }

        if (request()->filled('expiring_in')) {
            $days = (int) request('expiring_in');
            $query->whereHas('lastSubscription.currentPeriod', function ($q) use ($days) {
                $q->where('period_end', '<=', now()->addDays($days))
                    ->where('period_end', '>=', now()->startOfDay());
            });
        }

        return $query;
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'company_name', 'name' => 'company_name', 'title' => 'اسم العميل / الشركة'],
            ['data' => 'primary_contact_name', 'name' => 'primary_contact_name', 'title' => 'المسؤول الرئيسي'],
            ['data' => 'city', 'name' => 'city_id', 'title' => 'المدينة'],
            ['data' => 'tax_number', 'name' => 'tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'commercial_number', 'name' => 'commercial_number', 'title' => 'السجل التجاري'],
            ['data' => 'plan', 'name' => 'plan', 'title' => 'الباقة الحالية', 'orderable' => false, 'searchable' => false],
            ['data' => 'starts_at', 'name' => 'starts_at', 'title' => 'بداية الاشتراك', 'orderable' => false, 'searchable' => false],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'نهاية الاشتراك', 'orderable' => false, 'searchable' => false],
            ['data' => 'days_remaining', 'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false, 'searchable' => false],
            ['data' => 'subscription_status', 'name' => 'subscription_status', 'title' => 'حالة الاشتراك', 'orderable' => false, 'searchable' => false],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '110px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable->addColumn('checkbox', fn (Tenant $row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">');

        $datatable
            ->editColumn('company_name', function (Tenant $row) {
                return '<span class="fw-semibold text-heading">'.e($row->company_name ?: ($row->id)).'</span>';
            })
            ->addColumn('primary_contact_name', fn (Tenant $row) => $row->primary_contact_name ?: '-')
            ->addColumn('city', fn (Tenant $row) => $row->city?->name ?: '-')
            ->editColumn('tax_number', fn (Tenant $row) => $row->tax_number ? '<span class="font-monospace">'.e($row->tax_number).'</span>' : '-')
            ->editColumn('commercial_number', fn (Tenant $row) => $row->commercial_number ? '<span class="font-monospace">'.e($row->commercial_number).'</span>' : '-')
            ->addColumn('plan', fn (Tenant $row) => $row->lastSubscription?->plan?->name ? '<span class="badge bg-label-primary">'.e($row->lastSubscription->plan->name).'</span>' : '<span class="text-muted">-</span>')
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
                $periodEnd = $period?->period_end ?? $sub->ends_at;

                if (! $periodEnd) {
                    return '<span class="badge bg-secondary">-</span>';
                }

                $today = now();
                if ($periodEnd->lt($today)) {
                    return '<span class="badge bg-label-danger">منتهي (0 يوم)</span>';
                }

                $days = (int) $today->diffInDays($periodEnd, false) + 1;

                if ($days <= 7) {
                    return '<span class="badge bg-label-danger fw-bold"><i class="ti ti-alert-triangle ti-xs me-1"></i>'.$days.' أَيَّام</span>';
                }
                if ($days <= 30) {
                    return '<span class="badge bg-label-warning fw-semibold"><i class="ti ti-clock ti-xs me-1"></i>'.$days.' يوماً</span>';
                }

                return '<span class="badge bg-label-success">'.$days.' يوماً</span>';
            })
            ->addColumn('subscription_status', function (Tenant $row) {
                $sub = $row->lastSubscription;
                if (! $sub || ! $sub->status) {
                    return '<span class="badge bg-label-secondary">لا يوجد</span>';
                }

                $statusEnum = $sub->status instanceof SubscriptionStatus
                    ? $sub->status
                    : SubscriptionStatus::tryFrom((string) $sub->status);

                $label = $statusEnum ? $statusEnum->label() : (string) $sub->status;
                $color = $statusEnum ? $statusEnum->color() : 'primary';

                return '<span class="badge bg-label-'.e($color).'">'.e($label).'</span>';
            })
            ->addColumn('actions', fn (Tenant $row) => view('admin.tenants.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'company_name', 'tax_number', 'commercial_number', 'plan', 'days_remaining', 'subscription_status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['company_name', 'tax_number', 'commercial_number', 'id', 'primary_contact_name', 'primary_contact_phone', 'primary_contact_email'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'asc'];
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

        if (auth()->user()?->can('tenants.delete') || auth()->user()?->can('companies.delete')) {
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
        if (auth()->user()?->can('tenants.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة عميل',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route('admin.tenants.create')."'; }",
            ];
        }

        return [];
    }
}

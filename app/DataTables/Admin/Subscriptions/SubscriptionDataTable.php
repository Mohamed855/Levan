<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Subscriptions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscriptions\Subscription;

class SubscriptionDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'subscriptions-table';
    }

    protected function resource()
    {
        $query = Subscription::query()
            ->with(['tenant', 'plan', 'currentPeriod', 'periods']);

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        if (request()->filled('plan_id')) {
            $query->where('plan_id', request('plan_id'));
        }

        if (request()->filled('billing_cycle')) {
            $query->whereHas('plan', function ($q) {
                $q->where('subscription_billing_cycle', request('billing_cycle'));
            });
        }

        if (request()->filled('start_from')) {
            $query->whereDate('starts_at', '>=', request('start_from'));
        }
        if (request()->filled('end_to')) {
            $query->whereDate('ends_at', '<=', request('end_to'));
        }

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
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'subscription_number', 'name' => 'subscription_number', 'title' => 'رقم الاشتراك'],
            ['data' => 'company_name', 'name' => 'tenant.company_name', 'title' => 'اسم الشركة'],
            ['data' => 'tax_number', 'name' => 'tenant.tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'plan_id', 'name' => 'plan.name', 'title' => 'الباقة'],
            ['data' => 'starts_at', 'name' => 'starts_at', 'title' => 'تاريخ البداية'],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'تاريخ النهاية'],
            ['data' => 'days_remaining', 'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة الاشتراك'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn (Subscription $row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('subscription_number', fn (Subscription $row) => '<span class="fw-bold text-primary">'.e($row->subscription_number).'</span>')
            ->addColumn('company_name', fn (Subscription $row) => $row->tenant?->company_name ?: ($row->tenant?->name ?: '-'))
            ->addColumn('tax_number', fn (Subscription $row) => $row->tenant?->tax_number ?: '-')
            ->editColumn('plan_id', fn (Subscription $row) => $row->plan?->name ?: '-')
            ->editColumn('starts_at', fn (Subscription $row) => $row->starts_at?->format('Y-m-d') ?: '-')
            ->editColumn('ends_at', function (Subscription $row) {
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();

                return $period?->period_end?->format('Y-m-d') ?: ($row->ends_at?->format('Y-m-d') ?: '-');
            })
            ->addColumn('days_remaining', function (Subscription $row) {
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();
                $periodEnd = $period?->period_end ?? $row->ends_at;

                if (! $periodEnd) {
                    return '<span class="badge bg-secondary">-</span>';
                }

                $today = now();
                if ($periodEnd->lt($today)) {
                    return '<span class="badge bg-label-danger">منتهي (0 يوم)</span>';
                }

                $days = (int) $today->diffInDays($periodEnd, false) + 1;

                if ($days <= 7) {
                    return '<span class="badge bg-label-danger fw-bold"><i class="ti ti-alert-triangle ti-xs me-1"></i>'.$days.' '.($days === 1 ? 'يوم' : ($days === 2 ? 'يومان' : 'أيام')).'</span>';
                }
                if ($days <= 30) {
                    return '<span class="badge bg-label-warning fw-semibold"><i class="ti ti-clock ti-xs me-1"></i>'.$days.' يوماً</span>';
                }

                return '<span class="badge bg-label-success">'.$days.' يوماً</span>';
            })
            ->editColumn('status', function (Subscription $row) {
                $statusEnum = $row->status instanceof SubscriptionStatus
                    ? $row->status
                    : SubscriptionStatus::tryFrom((string) $row->status);

                $label = $statusEnum ? $statusEnum->label() : (string) $row->status;
                $color = $statusEnum ? $statusEnum->color() : 'primary';

                return '<span class="badge bg-label-'.e($color).'">'.e($label).'</span>';
            })
            ->addColumn('actions', fn (Subscription $row) => view('admin.subscriptions.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'subscription_number', 'days_remaining', 'status', 'actions'];
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
            $this->getActionsDropdownButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('subscriptions.delete')) {
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
}

<?php

namespace App\DataTables\Admin\Companies;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Subscription\Subscription;
use App\Services\shared\Subscription\SubscriptionManager;

class CompanyDataTable extends AbstractDataTable
{
    protected function getTableId()
    {
        return 'companies-table';
    }

    protected function resource()
    {
        return Subscription::query()->with(['plan', 'currentPeriod', 'periods', 'tenant.city', 'tenant.version']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'company_name', 'name' => 'company_name', 'title' => 'اسم الشركة'],
            ['data' => 'city', 'name' => 'city_id', 'title' => 'المدينة'],
            ['data' => 'tax_number', 'name' => 'tax_number', 'title' => 'الرقم الضريبي'],
            ['data' => 'tenant_id', 'name' => 'tenant_id', 'title' => 'معرف العميل'],
            ['data' => 'version', 'name' => 'version', 'title' => 'رقم الإصدار'],
            ['data' => 'plan_id', 'name' => 'plan_id', 'title' => 'الباقة'],
            ['data' => 'total_users', 'name' => 'total_users', 'title' => 'عدد المستخدمين'],
            ['data' => 'used_users', 'name' => 'used_users', 'title' => 'التراخيص المستخدمة'],
            ['data' => 'starts_at', 'name' => 'starts_at', 'title' => 'بداية أول اشتراك'],
            ['data' => 'period_end', 'name' => 'period_end', 'title' => 'نهاية الاشتراك الحالي'],
            ['data' => 'ends_at', 'name' => 'ends_at', 'title' => 'نهاية الاشتراك'],
            ['data' => 'days_remaining', 'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => 'حالة الإشتراك'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->editColumn('company_name', fn ($row) => $row->tenant?->company_name ?? '-')
            ->addColumn('city', fn ($row) => $row->tenant?->city?->name ?? '-')
            ->addColumn('tax_number', fn ($row) => $row->tenant?->tax_number ?? '-')
            ->editColumn('tenant_id', function ($row) {
                $url = route('admin.companies.tenant-access', ['tenant' => $row->tenant_id]);

                return '<a href="'.$url.'" class="text-dark d-inline-block" title="رابط العميل" target="_blank">'
                    .'<i class="ti ti-external-link me-1 text-primary"></i>'
                    .$row->tenant_id
                    .'</a>';
            })
            ->editColumn('version', fn ($row) => $row->tenant?->version?->version ?? '-')
            ->editColumn('plan_id', fn ($row) => $row->plan?->name ?? '-')
            ->editColumn('starts_at', fn ($row) => $row->starts_at?->format('Y-m-d') ?? '-')
            ->editColumn('ends_at', fn ($row) => $row->ends_at?->format('Y-m-d') ?? '-')
            ->addColumn('period_end', function ($row) {
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();

                return $period?->period_end?->format('Y-m-d') ?? '-';
            })
            ->addColumn('days_remaining', function ($row) {
                $period = $row->currentPeriod ?? $row->periods->sortByDesc('period_end')->first();
                $days = app(SubscriptionManager::class)->calculateDaysRemaining($period);

                if ($days === null) {
                    return '<span class="badge bg-label-secondary">-</span>';
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
            ->editColumn('status', function ($row) {
                if (! $row->status) {
                    return '<span class="badge bg-label-secondary">-</span>';
                }

                return '<span class="badge bg-'.$row->status->color().'">'.$row->status->label().'</span>';
            })
            ->addColumn('actions', fn ($row) => view('admin.companies.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['tenant_id', 'days_remaining', 'status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['tenant.company_name', 'tenant.tax_number', 'tenant_id'];
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
                    ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => ['columns' => [14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2]]],
                ],
            ],
            [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة شركة',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route('admin.companies.create')."'; }",
            ],
        ];
    }
}

<?php

namespace App\DataTables\Admin\Archive\Subscription;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Subscription\Subscription;
use App\Services\shared\Subscription\SubscriptionManager;

class SubscriptionDataTable extends AbstractDataTable
{
    private $route  = "admin.archives.subscriptions";
    protected function getTableId()
    {
        return "subscriptions-archive-table";
    }


    protected function resource()
    {
        return Subscription::query()->onlyTrashed()->with(['plan', 'currentPeriod', 'tenant']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'company_name',      'name' => 'company_name',   'title' => 'اسم المنشأة'],
            ['data' => 'tenant_id',         'name' => 'tenant_id',      'title' => 'معرف العميل'],
            ['data' => 'plan_id',          'name' => 'plan_id',       'title' => 'الباقة'],
            ['data' => 'total_users',    'name' => 'total_users', 'title' => 'عدد المستخدمين '],
            ['data' => 'used_users',      'name' => 'used_users',   'title' => 'التراخيص المستخدمة'],
            ['data' => 'starts_at',         'name' => 'starts_at',      'title' => 'بداية اول اشتراك',],
            ['data' => 'period_end',        'name' => 'period_end',     'title' => 'نهاية الإشتراك الحالي',],
            ['data' => 'status',            'name' => 'status',         'title' => 'الحالة',],

            ['data' => 'days_remaining',    'name' => 'days_remaining', 'title' => 'الأيام المتبقية', 'orderable' => false,],
            ['data' => 'actions',           'name' => 'actions',        'title' => 'الإجراءات',       'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }




    protected function addCustomColumns($datatable)
    {


        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('company_name', function ($row) {
                return $row->tenant->company_name ?? '-';
            })

            ->editColumn('plan_id', function ($row) {
                return $row->plan->name ?? '-';
            })




            ->editColumn('starts_at', function ($row) {
                return $row->starts_at?->format('Y-m-d') ?? '-';
            })

            ->editColumn('period_end', function ($row) {
                $latestPeriod = $row->periods()->latest('period_end')->first();

                return $latestPeriod?->period_end?->format('Y-m-d') ?? '-';
            })


            // عمود الأيام المتبقية
            ->addColumn('days_remaining', function ($row) {
                $latestPeriod = $row->periods()->latest('period_end')->first();
                $service = app(SubscriptionManager::class);

                return $service->calculateDaysRemaining($latestPeriod);
            })

            ->editColumn('status', function ($row) {
                return $this->StageBadge($row->status);
            })



            ->editColumn('price', function ($row) {
                return number_format($row->price, 2) . ' ر.س';
            })
            ->addColumn('actions', function ($row) {
                return view('admin.archive.subscription.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return $query;
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'tenant_id', 'plan_id', 'billing_cycle', 'status', 'actions'];
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
            $this->getAddButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل',  'exportOptions' => [
                'columns' => [11, 10, 9, 8, 7, 6, 5, 4, 3]
            ]],
        ];

        // if (auth()->user()?->can('versions.delete')) {
        $buttons[] = [
            'text'      => 'حذف المحدد',
            'className' => 'btn btn-default btn-delete-selected',
            'action'    => "function() { handleMassDelete(); }",
        ];
        // }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        // if (!auth()->user()?->can('إضافة إشتراك')) {
        //     return [];
        // }

        return [];
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function TypeBadge($billing_cycle): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $billing_cycle->color(),
            $billing_cycle->label()
        );
    }

    private function StageBadge($status): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $status->color(),
            $status->label()
        );
    }
}

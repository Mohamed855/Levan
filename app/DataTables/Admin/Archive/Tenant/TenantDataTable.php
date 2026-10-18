<?php

namespace App\DataTables\Admin\Archive\Tenant;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Tenants\Tenant;

class TenantDataTable extends AbstractDataTable
{
    public $route = 'admin.archives.tenants';

    protected function getTableId()
    {
        return 'tenant-archive-table';
    }

    protected function resource()
    {
        return Tenant::query()->onlyTrashed()->with('lastSubscription');
    }

    protected function getColumns(): array
    {
        return [
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
            ['data' => 'id',                   'name' => 'id',                 'title' => 'المعرف'],
            ['data' => 'company_name',         'name' => 'company_name',       'title' => 'اسم الشركة'],
            ['data' => 'commercial_number',    'name' => 'commercial_number',  'title' => 'السجل التجاري'],
            ['data' => 'deleted_at',           'name' => 'deleted_at',         'title' => 'حالة العميل', 'orderable' => false],
            ['data' => 'subscription_status',  'name' => 'subscription_status', 'title' => 'حالة الاشتراك', 'orderable' => false],
            ['data' => 'actions',              'name' => 'actions',            'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->addColumn('deleted_at', function ($row) {
                if ($row->deleted_at !== null) {
                    return '<span class="badge bg-label-danger">تم حذف العميل</span>';
                } else {
                    return '<span class="badge bg-label-success">نشط</span>';
                }
            })

            ->addColumn('subscription_status', function ($row) {
                if (! $row->lastSubscription) {
                    return '<span class="badge bg-secondary">-</span>';
                }

                return $this->SubscriptionStatus($row->lastSubscription->status);
            })

            ->addColumn('actions', function ($row) {
                return view('admin.archive.tenant.action', ['id' => $row->id, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'deleted_at', 'subscription_status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('status')) {
            $query->whereHas('lastSubscription', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        if ($request->filled('statusClient')) {
            if ($request->statusClient == '1') {
                $query->whereNull('deleted_at');
            } elseif ($request->statusClient == '0') {
                $query->whereNotNull('deleted_at');
            }
        }

        return $query;
    }

    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'company_name',
            'commercial_number',
        ];
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
                'columns' => [7, 6, 5, 4, 3],
            ]],
        ];

        if (auth()->user()?->can('clients.delete')) {
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
        return [];
    }

    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function SubscriptionStatus($status): string
    {
        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $status->color(),
            $status->label()
        );
    }
}

<?php

namespace App\DataTables\Admin\TenantManagement;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Tenants\Tenant;

class TenantManagementDataTable extends AbstractDataTable
{
    private $page  = "admin.tenant_managment";
    private $route = "admin.tenantManage";

    // table id
    protected function getTableId()
    {
        return "tenant-table";
    }

    protected function resource()
    {
        return Tenant::query()->with('lastSubscription');
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
                'width'      => '10px',
                'className'  => 'custom-checkbox',
                'titleAttr'  => 'تحديد الكل',
            ],
            ['data'  => 'id',                   'name' => 'id',                 'title' => 'المعرف'],
            ['data'  => 'company_name',         'name' => 'company_name',       'title' => 'اسم المنشأة'],
            ['data'  => 'version',              'name' => 'version',            'title' => 'رقم الإصدار'],
            ['data'  => 'tenant_link',          'name' => 'tenant_link',       'title' => 'رابط النسخه'],
            ['data'  => 'commercial_number',    'name' => 'commercial_number',  'title' => 'السجل التجاري'],
            ['data'  => 'subscription_status',  'name' => 'subscription_status', 'title' => 'حالة الاشتراك', 'orderable' => false],
            ['data'  => 'actions',              'name' => 'actions',            'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            // ->editColumn('id', function ($row) {
            //     return '<a href="' . route('admin.tenantManage.show', $row) . '" class="text-primary">' . $row->id . '</a>';
            // } )

            ->addColumn('deleted_at', function ($row) {
                if ($row->deleted_at !== null) {
                    return '<span class="badge bg-label-danger">تم حذف العميل</span>';
                } else {
                    return '<span class="badge bg-label-success">نشط</span>';
                }
            })
            ->editColumn('id', function ($row) {

                return '<a href="' . route('admin.tenantManage.show', $row) . '" class="text-primary">' . $row->id . '</a>';
            })
            ->editColumn('version', function ($row) {
                return $row->version->version;
            })
            ->editColumn('tenant_link', function ($row) {
                return '<span
        class="text-dark d-inline-block"
        style="max-width:150px; word-wrap:break-word; overflow-wrap:break-word;"
        title="رابط النسخه">'
                    . url($row->id . '/login') .
                    '</span>';
            })




            ->addColumn('subscription_status', function ($row) {
                if (!$row->lastSubscription) {
                    return '<span class="badge bg-label-secondary">-</span>';
                }

                return $this->SubscriptionStatus($row->lastSubscription->status);
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['id', 'checkbox', 'deleted_at', 'subscription_status', 'actions', 'tenant_link'];
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
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [7, 6, 5, 4, 3]
            ]],
        ];

        if (auth()->user()?->can('حذف عميل')) {
            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); } ",
            ];
        }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        if (auth()->user()?->can('إضافة عميل')) {
            return [
                'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة عميل',
                'className' => 'btn btn-primary btn-add',
                'action'    => "function() { window.location.href='" . route($this->route . '.create') . "'; } ",
            ];
        } else {
            return [];
        }
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

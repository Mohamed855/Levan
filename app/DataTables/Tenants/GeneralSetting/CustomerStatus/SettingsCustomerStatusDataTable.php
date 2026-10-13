<?php

namespace App\DataTables\Tenants\GeneralSetting\CustomerStatus;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use Carbon\Carbon;

class SettingsCustomerStatusDataTable extends AbstractDataTable
{
    /**
     * @var string The route name for actions (edit, delete).
     */
    private $route = 'general-settings.settings-customer-status';

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
    | يقوم هذا القسم ببناء استجابة JSON النهائية لـ DataTable. يضمن عرض
    | أعمدة HTML الخام بشكل صحيح عن طريق تطبيق `rawColumns` في النهاية.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();
        $this->addCustomColumns($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Get Table ID
    |--------------------------------------------------------------------------
    | يُرجع المُعرّف الفريد (ID) لوسم الجدول في HTML.
    */
    protected function getTableId()
    {
        return 'customer-status-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource
    |--------------------------------------------------------------------------
    | يُعرّف استعلام Eloquent الأساسي الذي سيتم استخدامه في DataTable.
    */
    protected function resource()
    {
        $tableName = (new SettingsCustomerStatus)->getTable();

        $query = SettingsCustomerStatus::with('user')->select([
            "{$tableName}.id",
            "{$tableName}.name",
            "{$tableName}.status",
            "{$tableName}.created_at",
        ]);

        return $this->applyCustomFilters($query);
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    | يحدد هيكل وخصائص أعمدة الجدول.
    */
    protected function getColumns(): array
    {
        return [
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
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم الحالة', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء', 'width' => '120px'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '100px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Modify and Add Custom Columns
    |--------------------------------------------------------------------------
    | يضيف ويُنسّق الأعمدة المخصصة، ويحول البيانات الخام إلى HTML.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('name', function ($row) {
                return '<span class="text-body fw-medium">'.$row->name.'</span>';
            })
            ->editColumn('status', function ($row) {
                $checked = $row->status === 'active' ? 'checked' : '';
                $url = tenant_route_name('general-settings.settings-customer-status.toggleStatus', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة العميل"></div></div>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d') : '';
            })
            ->addColumn('actions', function ($row) {
                return view('tenants.general_setting.settings_customer_status.action', [
                    'id' => $row->id,
                    'route' => $this->route,
                    'name' => $row->name,
                    'status' => $row->status,
                ])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Specify Raw HTML Columns
    |--------------------------------------------------------------------------
    | يسرد الأعمدة التي يجب عرضها كـ HTML خام دون معالجتها.
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'name', 'status', 'actions'];
    }

    /*
    |--------------------------------------------------------------------------
    | Define DataTable Buttons
    |--------------------------------------------------------------------------
    | يُرجع مصفوفة الأزرار التي ستُعرض فوق DataTable.
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Get Actions Dropdown Button
    |--------------------------------------------------------------------------
    | يبني زر القائمة المنسدلة "الإجراءات" ويحدد جميع العناصر الموجودة بداخله.
    */
    private function getActionsDropdownButton(): array
    {
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
                [
                    'text' => 'حذف المحدد',
                    'className' => 'btn btn-default btn-delete-selected',
                    'action' => 'function() { handleMassDelete(); }',
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Get "Add New" Button
    |--------------------------------------------------------------------------
    | يحدد زر "إضافة حالة جديدة".
    */
    private function getAddButton(): array
    {
        return [
            'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة حالة جديدة',
            'className' => 'btn btn-primary btn-add',
            'action' => "function(){ $('#addModal').modal('show'); }",
        ];
    }
}

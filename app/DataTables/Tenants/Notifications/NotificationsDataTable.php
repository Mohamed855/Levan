<?php

namespace App\DataTables\Tenants\Notifications;

use App\DataTables\Abstract\AbstractDataTable;
use Carbon\Carbon;

class NotificationsDataTable extends AbstractDataTable
{
    private $route = 'notifications.all';

    /*
    |--------------------------------------------------------------------------
    | Build DataTable
    |--------------------------------------------------------------------------
    | Build the DataTable response and mark raw columns.
    */
    public function dataTable($query)
    {
        $datatable = datatables()->eloquent($query)->addIndexColumn();

        $this->addCustomColumns($datatable);

        return $datatable->rawColumns($this->rawColumns());
    }

    /*
    |--------------------------------------------------------------------------
    | Table Id
    |--------------------------------------------------------------------------
    | Provide the unique HTML id for the table element.
    */
    protected function getTableId()
    {
        return 'notifications-table';
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Query
    |--------------------------------------------------------------------------
    | Define the base query for the DataTable.
    */
    protected function resource()
    {
        $user = auth('tenant')->user();

        return $user->notifications()->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    | Define the table columns structure.
    */
    protected function getColumns(): array
    {
        return [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data' => 'title', 'name' => 'data->title', 'title' => 'العنوان', 'width' => '200px', 'className' => 'text-start'],
            ['data' => 'message', 'name' => 'data->message', 'title' => 'الرسالة', 'width' => '300px'],
            ['data' => 'type', 'name' => 'data->type', 'title' => 'النوع', 'width' => '100px'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التاريخ', 'width' => '120px'],
            ['data' => 'read_at', 'name' => 'read_at', 'title' => 'الحالة', 'width' => '100px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Columns
    |--------------------------------------------------------------------------
    | Add/format custom columns.
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('title', function ($row) {
                $icon = $row->data['icon'] ?? 'ti-bell';
                $iconColors = [
                    'success' => 'text-success',
                    'warning' => 'text-warning',
                    'info' => 'text-info',
                    'danger' => 'text-danger',
                    'primary' => 'text-primary',
                    'secondary' => 'text-secondary',
                ];
                $colorClass = $iconColors[$row->data['type'] ?? 'info'] ?? 'text-info';

                return '<div class="d-flex align-items-center">
                    <i class="'.e($icon).' '.$colorClass.' me-2"></i>
                    <span>'.e($row->data['title'] ?? '').'</span>
                </div>';
            })
            ->editColumn('message', function ($row) {
                $message = e($row->data['message'] ?? '');

                return '<span class="text-truncate d-block" style="max-width: 300px;" title="'.$message.'">'.$message.'</span>';
            })
            ->editColumn('type', function ($row) {
                $type = $row->data['type'] ?? 'info';
                $typeLabels = [
                    'success' => 'نجاح',
                    'warning' => 'تحذير',
                    'info' => 'معلومات',
                    'danger' => 'خطر',
                    'primary' => 'أساسي',
                    'secondary' => 'ثانوي',
                ];
                $typeText = $typeLabels[$type] ?? 'معلومات';

                $typeColors = [
                    'success' => 'bg-label-success',
                    'warning' => 'bg-label-warning',
                    'info' => 'bg-label-info',
                    'danger' => 'bg-label-danger',
                    'primary' => 'bg-label-primary',
                    'secondary' => 'bg-secondary',
                ];
                $colorClass = $typeColors[$type] ?? 'bg-label-info';

                return '<span class="badge '.$colorClass.'">'.$typeText.'</span>';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d');
            })
            ->editColumn('read_at', function ($row) {
                if ($row->read_at) {
                    return '<span class="badge bg-label-success">مقروء</span>';
                } else {
                    return '<span class="badge bg-label-danger cursor-pointer" onclick="markAsRead('.$row->id.')">غير مقروء</span>';
                }
            })
            ->addColumn('actions', function ($row) {
                // استخدام ملف Blade منفصل للأزرار
                return view('tenant.notifications.action', [
                    'id' => $row->id,
                    'notification' => $row,
                ])->render();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Columns
    |--------------------------------------------------------------------------
    | Specify columns that contain raw HTML.
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'title', 'message', 'type', 'read_at', 'actions'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    | Apply custom filters coming from the UI.
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('data->type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($request->status === 'unread') {
                $query->whereNull('read_at');
            }
        }

        if ($request->filled('search_query')) {
            $searchTerm = $request->search_query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('data->title', 'like', "%{$searchTerm}%")
                    ->orWhere('data->message', 'like', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    | Define DataTable top buttons.
    */
    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Dropdown Button
    |--------------------------------------------------------------------------
    | Build the 'Actions' dropdown content.
    */
    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        $buttons[] = [
            'text' => 'تحديد الكل كمقروء',
            'className' => 'btn btn-default btn-mark-all-read',
            'action' => 'function() { markAllAsRead(); }',
        ];

        $buttons[] = [
            'text' => 'حذف المحدد',
            'className' => 'btn btn-default btn-delete-selected',
            'action' => 'function() { handleMassDelete(); }',
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}

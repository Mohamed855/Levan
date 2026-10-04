<?php

namespace App\DataTables\Tenants\BusinessCenter\Tasks;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Services\Tenants\BusinessCenter\Task\TaskService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MyTasksDataTable extends AbstractDataTable
{
    private $route = "my-tasks";
    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /*
    |--------------------------------------------------------------------------
    | Build DataTable Response
    |--------------------------------------------------------------------------
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
    */
    protected function getTableId()
    {
        return "my-tasks-table";
    }

    /*
    |--------------------------------------------------------------------------
    | Define Query Resource
    |--------------------------------------------------------------------------
    */
    protected function resource(): Builder
    {
        $query = $this->taskService->getMyTasksQuery();
        $query = $this->applyCustomFilters($query);

        // Default ordering
        $overdueDaysExpr = 'DATEDIFF(CURDATE(), tasks.due_date)';
        $remainingDebtExpr = '(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) FROM customer_transactions WHERE customer_id = tasks.customer_id)';

        $query->orderByRaw("$overdueDaysExpr DESC")
            ->orderBy('tasks.due_date', 'asc')
            ->orderBy('tasks.created_at', 'desc')
            ->orderBy('tasks.id', 'desc');

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Define Table Columns
    |--------------------------------------------------------------------------
    */
    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
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
            ['data' => 'customer_name', 'name' => 'c.name', 'title' => 'العميل'],
            ['data' => 'account_number', 'name' => 'c.account_number', 'title' => 'رقم الحساب'],
            ['data' => 'identity_number', 'name' => 'c.identity_number', 'title' => 'رقم الهوية'],
            ['data' => 'company_name', 'name' => 'cmp.name', 'title' => 'الشركة', 'orderable' => false],
            ['data' => 'portfolio_name', 'name' => 'prt.name', 'title' => 'المحفظة', 'orderable' => false],
            ['data' => 'task_type', 'name' => 'tasks.task_type', 'title' => 'نوع المهمة'],
            ['data' => 'due_date', 'name' => 'tasks.due_date', 'title' => 'التاريخ المحدد'],
            ['data' => 'overdue_days', 'name' => 'overdue_days', 'title' => 'أيام التأخير'],
            ['data' => 'status', 'name' => 'tasks.status', 'title' => 'الحالة'],
            ['data' => 'remaining_debt', 'name' => 'remaining_debt', 'title' => 'المديونية'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Column Modifications
    |--------------------------------------------------------------------------
    */
    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')

            ->addColumn('customer_name', function ($row) {
                $showUrl = tenant_route_name('customers.show', $row->customer_id);
                $phone = $row->customer_phone ?: 'غير محدد';
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div class="flex-grow-1 min-w-0">'
                    . '<a href="' . $showUrl . '" class="text-decoration-none text-primary fw-semibold" data-bs-toggle="tooltip" title="انقر لعرض تفاصيل العميل">'
                    . e($row->customer_name) . '</a>'
                    . '<div class="small text-muted">' . e($phone) . '</div>'
                    . '</div></div>';
            })

            ->editColumn('account_number', fn($row) => e($row->account_number ?: 'غير محدد'))
            ->editColumn('identity_number', fn($row) => e($row->identity_number ?: 'غير محدد'))

            ->addColumn('company_name', fn($row) => '<span class="text-primary">' . e($row->company_name ?: 'غير محدد') . '</span>')
            ->addColumn('portfolio_name', fn($row) => e($row->portfolio_name ?: 'غير محدد'))

            ->addColumn('task_type', function ($row) {
                $badgeClasses = [
                    'promise' => ['label' => 'وعد بالسداد', 'class' => 'bg-label-success'],
                    'callback' => ['label' => 'معاودة اتصال', 'class' => 'bg-label-primary'],
                    'document_request' => ['label' => 'طلب مستندات', 'class' => 'bg-label-warning'],
                ];
                $badge = $badgeClasses[$row->task_type] ?? ['label' => 'غير محدد', 'class' => 'bg-secondary'];
                return '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
            })

            ->addColumn('due_date', fn($row) => $row->due_date ? Carbon::parse($row->due_date)->format('Y-m-d') : '<span class="text-muted">غير محدد</span>')

            ->addColumn('overdue_days', function ($row) {
                $days = (int)$row->overdue_days;

                if ($days < 0) {
                    return '<span class="badge bg-label-info">متبقي ' . abs($days) . ' يوم</span>';
                } elseif ($days == 0) {
                    return '<span class="badge bg-label-warning">اليوم</span>';
                } elseif ($days <= 3) {
                    return '<span class="badge bg-label-warning fw-bold">' . $days . ' يوم</span>';
                } elseif ($days <= 7) {
                    return '<span class="badge bg-label-danger fw-bold">' . $days . ' أيام</span>';
                } else {
                    return '<span class="badge bg-danger text-white fw-bold">' . $days . ' يوم</span>';
                }
            })

            ->addColumn('status', function ($row) {
                if ($row->status === 'completed') {
                    return '<span class="badge bg-label-success">مكتملة</span>';
                }
                return '<span class="badge bg-label-warning">معلقة</span>';
            })

            ->addColumn('remaining_debt', fn($row) => number_format((float)$row->remaining_debt, 2) . ' ريال');
    }

    /*
    |--------------------------------------------------------------------------
    | Raw HTML Columns
    |--------------------------------------------------------------------------
    */
    protected function rawColumns(): array
    {
        return ['checkbox', 'customer_name', 'company_name', 'task_type', 'due_date', 'overdue_days', 'status'];
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Custom Filters
    |--------------------------------------------------------------------------
    */
    protected function applyCustomFilters($query)
    {
        $request = request();

        $filters = [
            'company_id' => $request->input('company_id'),
            'portfolio_id' => $request->input('portfolio_id'),
            'task_type' => $request->input('task_type'),
            'status' => $request->input('status'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        return $this->taskService->applyFilters($query, $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Define All DataTable Buttons
    |--------------------------------------------------------------------------
    */
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
            [
                'text' => 'إكسل',
                'className' => 'btn-export-excel',
                'action' => "function(e, dt, node, config) { 

                const filters = {
                    company_id: $('#filter-company').val() || '',
                    portfolio_id: $('#filter-portfolio').val() || '',
                    task_type: $('#filter-task-type').val() || '',
                    status: $('#filter-status').val() || '',
                    overdue_days: $('#filter-overdue-days').val() || '',
                    date_from: $('#filter-date-from').val() || '',
                    date_to: $('#filter-date-to').val() || ''
                };
                
                // بناء URL مع الفلاتر
                const params = new URLSearchParams();
                for (const key in filters) {
                    if (filters[key] && filters[key] !== '') {
                        params.append(key, filters[key]);
                    }
                }
                
                const exportUrl = '" . tenant_route_name('my-tasks.export') . "';
                const finalUrl = params.toString() ? exportUrl + '?' + params.toString() : exportUrl;
                
                window.location.href = finalUrl;
            }"
            ],
        ];

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export btn',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}

<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Task;

use App\DataTables\Tenants\BusinessCenter\Tasks\MyTasksDataTable;
use App\Exports\Tenants\BusinessCenter\Task\MyTasksExport;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Services\Tenants\BusinessCenter\Task\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MyTaskController extends Controller
{
    protected TaskService $taskService;

    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;

        $this->middleware('auth');
        $this->middleware('can:مهامي')->only(['index']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(MyTasksDataTable $dataTable): mixed
    {
        $statistics = $this->taskService->getMyTaskStatistics();
        $companies = $this->taskService->getActiveCompanies();
        $portfolios = $this->taskService->getActivePortfolios();

        return $dataTable->render('tenants.business_center.my_tasks.index', [
            'totalTasks' => $statistics['total'],
            'pendingTasks' => $statistics['pending'],
            'completedTasks' => $statistics['completed'],
            'overdueTasks' => $statistics['overdue'],
            'companies' => $companies,
            'portfolios' => $portfolios,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Export to Excel
    |--------------------------------------------------------------------------
    */
    public function export(Request $request)
    {
        $query = $this->taskService->getMyTasksQuery();

        $filters = [
            'company_id' => $request->input('company_id'),
            'portfolio_id' => $request->input('portfolio_id'),
            'task_type' => $request->input('task_type'),
            'status' => $request->input('status'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = $this->taskService->applyFilters($query, $filters);
        $overdueDaysExpr = 'DATEDIFF(CURDATE(), tasks.due_date)';
        $query->orderByRaw("$overdueDaysExpr DESC")
            ->orderBy('tasks.due_date', 'asc')
            ->orderBy('tasks.created_at', 'desc')
            ->orderBy('tasks.id', 'desc');
        $tasks = $query->get();
        $fileName = 'my_tasks_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new MyTasksExport($tasks), $fileName);
    }
}

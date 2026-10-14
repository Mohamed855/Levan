<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Task;

use App\DataTables\Tenants\BusinessCenter\Tasks\TasksDataTable;
use App\Exports\Tenants\BusinessCenter\Task\TasksExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Task\ExportTasksRequest;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Services\Tenants\BusinessCenter\Task\TaskService;
use Maatwebsite\Excel\Facades\Excel;

class TaskController extends Controller
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
        $this->middleware('can:tasks.view')->only(['index']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(TasksDataTable $dataTable): mixed
    {
        $this->authorize('viewAny', Task::class);

        $statistics = $this->taskService->getTaskStatistics();
        $companies = $this->taskService->getActiveCompanies();
        $portfolios = $this->taskService->getActivePortfolios();
        $users = $this->taskService->getActiveUsers();

        return $dataTable->render('tenants.business_center.tasks.index', [
            'totalTasks' => $statistics['total'],
            'pendingTasks' => $statistics['pending'],
            'completedTasks' => $statistics['completed'],
            'dueTodayTasks' => $statistics['due_today'],
            'overdueTasks' => $statistics['overdue'],
            'companies' => $companies,
            'portfolios' => $portfolios,
            'users' => $users,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Export to Excel
    |--------------------------------------------------------------------------
    */
    public function export(ExportTasksRequest $request)
    {
        $query = $this->taskService->getAllTasksQuery();
        $filters = $request->validated();

        $query = $this->taskService->applyFilters($query, $filters);
        $query->orderByRaw('DATEDIFF(CURDATE(), tasks.due_date) DESC')
            ->orderBy('rm.name', 'ASC')
            ->orderByRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) FROM customer_transactions WHERE customer_id = tasks.customer_id) DESC');
        $tasks = $query->get();
        $fileName = 'tasks_report_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new TasksExport($tasks), $fileName);
    }
}

<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Task;

use App\DataTables\Tenants\BusinessCenter\Tasks\TasksDataTable;
use App\Exports\Tenants\BusinessCenter\Task\TasksExport;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Services\Tenants\BusinessCenter\Task\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
        $this->middleware('can:المهام')->only(['index']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(TasksDataTable $dataTable): mixed
    {
        $statistics = $this->taskService->getTaskStatistics();
        $companies = $this->taskService->getActiveCompanies();
        $portfolios = $this->taskService->getActivePortfolios();
        $users = $this->taskService->getActiveUsers();

        return $dataTable->render('tenants.business_center.tasks.index', [
            'totalTasks' => $statistics['total'],
            'pendingTasks' => $statistics['pending'],
            'completedTasks' => $statistics['completed'],
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
    public function export(Request $request)
    {

        $query = $this->taskService->getAllTasksQuery();
        $filters = [
            'company_id' => $request->input('company_id'),
            'portfolio_id' => $request->input('portfolio_id'),
            'relationship_manager_id' => $request->input('relationship_manager_id'),
            'task_type' => $request->input('task_type'),
            'status' => $request->input('status'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = $this->taskService->applyFilters($query, $filters);
        $query->orderByRaw('DATEDIFF(CURDATE(), tasks.due_date) DESC')
            ->orderBy('rm.name', 'ASC')
            ->orderByRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) FROM customer_transactions WHERE customer_id = tasks.customer_id) DESC');
        $tasks = $query->get();
        $fileName = 'tasks_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new TasksExport($tasks), $fileName);
    }
}

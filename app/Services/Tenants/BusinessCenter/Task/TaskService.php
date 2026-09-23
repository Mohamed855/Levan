<?php

namespace App\Services\Tenants\BusinessCenter\Task;

use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /*
    |--------------------------------------------------------------------------
    | Get All Tasks Query (للإدارة)
    |--------------------------------------------------------------------------
    */
    public function getAllTasksQuery(): Builder
    {
        $remainingDebtExpr = '(SELECT
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
            FROM customer_transactions
            WHERE customer_id = tasks.customer_id)';

        $overdueDaysExpr = 'DATEDIFF(CURDATE(), tasks.due_date)';

        return Task::select([
            'tasks.*',
            'c.name as customer_name',
            'c.phone as customer_phone',
            'c.account_number',
            'c.identity_number',
            'cmp.name as company_name',
            'prt.name as portfolio_name',
            'rm.name as relationship_manager_name',
            'rm.employee_number as relationship_manager_emp',
            DB::raw("$remainingDebtExpr as remaining_debt"),
            DB::raw("$overdueDaysExpr as overdue_days")
        ])
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'c.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'c.portfolio_id')
            ->leftJoin('users as rm', 'rm.id', '=', 'c.relationship_manager_id')
            ->whereRaw("$remainingDebtExpr > 0");
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Tasks Query (للموظف)
    |--------------------------------------------------------------------------
    */
    public function getMyTasksQuery(): Builder
    {
        $userId = Auth::id();

        $remainingDebtExpr = '(SELECT
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
            FROM customer_transactions
            WHERE customer_id = tasks.customer_id)';

        $overdueDaysExpr = 'DATEDIFF(CURDATE(), tasks.due_date)';

        return Task::select([
            'tasks.*',
            'c.name as customer_name',
            'c.phone as customer_phone',
            'c.account_number',
            'c.identity_number',
            'cmp.name as company_name',
            'prt.name as portfolio_name',
            DB::raw("$remainingDebtExpr as remaining_debt"),
            DB::raw("$overdueDaysExpr as overdue_days")
        ])
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'c.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'c.portfolio_id')
            ->where('c.relationship_manager_id', $userId)
            ->whereRaw("$remainingDebtExpr > 0");
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by company
        if (isset($filters['company_id']) && $filters['company_id'] !== '' && $filters['company_id'] !== null) {
            $query->where('c.company_id', $filters['company_id']);
        }

        // Filter by portfolio
        if (isset($filters['portfolio_id']) && $filters['portfolio_id'] !== '' && $filters['portfolio_id'] !== null) {
            $query->where('c.portfolio_id', $filters['portfolio_id']);
        }

        // Filter by relationship manager (للإدارة فقط)
        if (isset($filters['relationship_manager_id']) && $filters['relationship_manager_id'] !== '' && $filters['relationship_manager_id'] !== null) {
            $query->where('c.relationship_manager_id', $filters['relationship_manager_id']);
        }

        // Filter by task type
        if (isset($filters['task_type']) && $filters['task_type'] !== '' && $filters['task_type'] !== null) {
            $query->where('tasks.task_type', $filters['task_type']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->where('tasks.status', 'pending')
                    ->whereDate('tasks.due_date', '<', now()->toDateString());
            } else {
                $query->where('tasks.status', $filters['status']);
            }
        }

        // Filter by overdue days
        if (isset($filters['overdue_days']) && $filters['overdue_days'] !== '' && $filters['overdue_days'] !== null && $filters['overdue_days'] !== 'custom') {
            $overdueDaysExpr = 'DATEDIFF(CURDATE(), tasks.due_date)';
            $query->whereRaw("$overdueDaysExpr <= ?", [(int)$filters['overdue_days']]);
        }

        // Filter by date range
        if (
            isset($filters['date_from']) && $filters['date_from'] !== '' && $filters['date_from'] !== null &&
            isset($filters['date_to']) && $filters['date_to'] !== '' && $filters['date_to'] !== null
        ) {
            $query->whereBetween('tasks.due_date', [$filters['date_from'], $filters['date_to']]);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Task Statistics (للإدارة)
    |--------------------------------------------------------------------------
    */
    public function getTaskStatistics(): array
    {
        $today = now()->toDateString();

        $remainingDebtExpr = '(SELECT
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
            FROM customer_transactions
            WHERE customer_id = tasks.customer_id)';

        $baseQuery = Task::leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->whereRaw("$remainingDebtExpr > 0");

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('tasks.status', 'pending')->count(),
            'completed' => (clone $baseQuery)->where('tasks.status', 'completed')->count(),
            'overdue' => (clone $baseQuery)
                ->where('tasks.status', 'pending')
                ->whereDate('tasks.due_date', '<', $today)
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Task Statistics (للموظف)
    |--------------------------------------------------------------------------
    */
    public function getMyTaskStatistics(): array
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $remainingDebtExpr = '(SELECT
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
            FROM customer_transactions
            WHERE customer_id = tasks.customer_id)';

        $baseQuery = Task::leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->where('c.relationship_manager_id', $userId)
            ->whereRaw("$remainingDebtExpr > 0");

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('tasks.status', 'pending')->count(),
            'completed' => (clone $baseQuery)->where('tasks.status', 'completed')->count(),
            'overdue' => (clone $baseQuery)
                ->where('tasks.status', 'pending')
                ->whereDate('tasks.due_date', '<', $today)
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Companies
    |--------------------------------------------------------------------------
    */
    public function getActiveCompanies()
    {
        return Company::select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Portfolios
    |--------------------------------------------------------------------------
    */
    public function getActivePortfolios()
    {
        return Portfolio::select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Users (للإدارة)
    |--------------------------------------------------------------------------
    */
    public function getActiveUsers()
    {
        return User::select('id', 'name', 'employee_number')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Task
    |--------------------------------------------------------------------------
    */
    public function deleteTask(Task $task): bool
    {
        return $task->delete();
    }
}

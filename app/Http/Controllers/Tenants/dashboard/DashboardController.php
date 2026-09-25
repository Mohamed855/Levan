<?php

namespace App\Http\Controllers\Tenants\Dashboard;

use App\DataTables\Tenants\FinancialCollection\CollectionProcess\CollectionSessionsDataTable;
use App\Exports\Tenants\Dashboard\Admin\CollectionSessionsExport;
use App\Exports\Tenants\Dashboard\OverdueTasksExport;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Services\Tenants\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{

    public function __construct(private DashboardService $dashboardService)
    {
        $this->middleware('can:لوحة تحكم الإدارة')->only('collectionSessionsPage');
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(Request $request = null)
    {
        if (!$request) {
            $request = request();
        }

        if ($request->ajax() && $request->has('period')) {
            return response()->json([
                'userPerformance' => $this->dashboardService->getPerformanceByPeriod($request->input('period')),
                'success' => true
            ]);
        }

        if (Auth::user()->can('لوحة تحكم الإدارة') || Auth::user()->canManageTeam()) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */
    private function adminDashboard()
    {
        $data = $this->dashboardService->getAdminDashboardData();
        $excellenceData = $this->dashboardService->getExcellenceDashboardData();
        $data = array_merge($data, $excellenceData);

        return view('tenants.dashboard.admin.dashboards', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | User Dashboard
    |--------------------------------------------------------------------------
    */
    private function userDashboard()
    {
        $data = $this->dashboardService->getUserDashboardData();

        return view('tenants.dashboard.user.dashboards', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Upcoming Follow Ups
    |--------------------------------------------------------------------------
    */
    public function getUpcomingFollowUps(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $accessibleUserIds = $this->getAccessibleUserIdsForDataTables();

        $remainingDebtExpr = '(SELECT
        COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
        FROM customer_transactions
        WHERE customer_id = tasks.customer_id)';

        $query = Task::select(
            'tasks.*',
            'c.name as customer_name',
            'c.phone as customer_phone',
            'c.account_number',
            'c.identity_number',
            'cmp.name as company_name',
            'prt.name as portfolio_name',
            'rm.name as relationship_manager_name',
            'rm.employee_number as relationship_manager_emp',
            DB::raw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) FROM customer_transactions WHERE customer_id = tasks.customer_id) as remaining_debt')
        )
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'c.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'c.portfolio_id')
            ->leftJoin('users as rm', 'rm.id', '=', 'c.relationship_manager_id')
            ->where('tasks.status', 'pending')
            ->whereDate('tasks.due_date', $today)
            ->whereNull('c.deleted_at')
            ->whereRaw("$remainingDebtExpr > 0");

        // ✅ تطبيق فلترة الفريق/الصلاحيات تلقائياً
        if ($accessibleUserIds !== null) {
            $query->whereIn('c.relationship_manager_id', $accessibleUserIds);
        }

        // ✅ تطبيق الفلاتر الاختيارية من الـ Request
        if ($request->filled('relationship_manager_id')) {
            $query->where('c.relationship_manager_id', $request->input('relationship_manager_id'));
        }

        if ($request->filled('company_id')) {
            $query->where('c.company_id', $request->input('company_id'));
        }

        if ($request->filled('portfolio_id')) {
            $query->where('c.portfolio_id', $request->input('portfolio_id'));
        }

        // ترتيب النتائج
        $query->orderBy('rm.name', 'asc')
            ->orderBy('tasks.due_date', 'asc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($row) {
                $showUrl = tenant_route_name('customers.show', $row->customer_id);
                $phone = $row->customer_phone ?: 'غير محدد';
                return '<div class="d-flex align-items-center gap-2">'
                    .   '<div class="flex-grow-1 min-w-0">'
                    .     '<a href="' . $showUrl . '" class="text-decoration-none text-dark">' . e($row->customer_name) . '</a>'
                    .     '<div class="small text-muted">' . e($phone) . '</div>'
                    .   '</div>'
                    . '</div>';
            })
            ->editColumn('account_number', fn($row) => e($row->account_number ?: 'غير محدد'))
            ->editColumn('identity_number', fn($row) => e($row->identity_number ?: 'غير محدد'))
            ->addColumn('company_name', fn($row) => e($row->company_name ?: 'غير محدد'))
            ->addColumn('portfolio_name', fn($row) => e($row->portfolio_name ?: 'غير محدد'))
            ->addColumn('remaining_debt', fn($row) => number_format((float)$row->remaining_debt, 2) . ' ريال')
            ->addColumn('follow_result', function ($row) {
                $badgeClasses = [
                    'promise' => 'bg-label-success',
                    'callback' => 'bg-label-primary',
                    'document_request' => 'bg-label-warning',
                ];
                $class = $badgeClasses[$row->task_type] ?? 'bg-label-secondary';
                $taskTypeName = Task::TASK_TYPES[$row->task_type] ?? 'غير محدد';
                return '<span class="badge ' . $class . '">' . e($taskTypeName) . '</span>';
            })
            ->addColumn('relationship_manager', function ($row) {
                $name = $row->relationship_manager_name ?: 'غير محدد';
                $emp = $row->relationship_manager_emp ?: 'غير محدد';
                return '<div class="text-center">'
                    .   '<div class="fw-semibold">' . e($name) . '</div>'
                    .   '<div class="small text-muted">' . e($emp) . '</div>'
                    . '</div>';
            })
            ->orderColumn('customer_name', 'c.name $1')
            ->orderColumn('account_number', 'c.account_number $1')
            ->orderColumn('identity_number', 'c.identity_number $1')
            ->orderColumn('company_name', 'cmp.name $1')
            ->orderColumn('portfolio_name', 'prt.name $1')
            ->orderColumn('remaining_debt', 'remaining_debt $1')
            ->orderColumn('follow_result', 'tasks.task_type $1')
            ->orderColumn('relationship_manager', 'rm.name $1')
            ->filterColumn('customer_name', fn($q, $k) => $q->where('c.name', 'like', "%{$k}%"))
            ->filterColumn('account_number', fn($q, $k) => $q->where('c.account_number', 'like', "%{$k}%"))
            ->filterColumn('identity_number', fn($q, $k) => $q->where('c.identity_number', 'like', "%{$k}%"))
            ->filterColumn('company_name', fn($q, $k) => $q->where('cmp.name', 'like', "%{$k}%"))
            ->filterColumn('portfolio_name', fn($q, $k) => $q->where('prt.name', 'like', "%{$k}%"))
            ->filterColumn('relationship_manager', fn($q, $k) => $q->where('rm.name', 'like', "%{$k}%"))
            ->rawColumns(['customer_name', 'follow_result', 'relationship_manager'])
            ->make(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Upcoming Follow Ups
    |--------------------------------------------------------------------------
    */
    public function getMyUpcomingFollowUps(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $remainingDebtExpr = '(SELECT
        COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
        FROM customer_transactions
        WHERE customer_id = tasks.customer_id)';

        $query = Task::select(
            'tasks.*',
            'c.name as customer_name',
            'c.phone as customer_phone',
            'c.account_number',
            'c.identity_number',
            'cmp.name as company_name',
            'prt.name as portfolio_name',
            DB::raw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) FROM customer_transactions WHERE customer_id = tasks.customer_id) as remaining_debt')
        )
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'c.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'c.portfolio_id')
            ->where('tasks.status', 'pending')
            ->whereDate('tasks.due_date', $today)
            ->where('c.relationship_manager_id', $userId)
            ->whereNull('c.deleted_at')
            ->whereRaw("$remainingDebtExpr > 0");

        // ترتيب النتائج
        $query->orderBy('tasks.due_date', 'asc')
            ->orderBy('tasks.created_at', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($row) {
                $showUrl = tenant_route_name('customers.show', $row->customer_id);
                $phone = $row->customer_phone ?: 'غير محدد';
                return '<div class="d-flex align-items-center gap-2">'
                    .   '<div class="flex-grow-1 min-w-0">'
                    .     '<a href="' . $showUrl . '" class="text-decoration-none text-dark">' . e($row->customer_name) . '</a>'
                    .     '<div class="small text-muted">' . e($phone) . '</div>'
                    .   '</div>'
                    . '</div>';
            })
            ->editColumn('account_number', fn($row) => e($row->account_number ?: 'غير محدد'))
            ->editColumn('identity_number', fn($row) => e($row->identity_number ?: 'غير محدد'))
            ->addColumn('company_name', fn($row) => e($row->company_name ?: 'غير محدد'))
            ->addColumn('portfolio_name', fn($row) => e($row->portfolio_name ?: 'غير محدد'))
            ->addColumn('remaining_debt', fn($row) => number_format((float)$row->remaining_debt, 2) . ' ريال')
            ->addColumn('follow_result', function ($row) {
                $badgeClasses = [
                    'promise' => 'bg-label-success',
                    'callback' => 'bg-label-primary',
                    'document_request' => 'bg-label-warning',
                ];
                $class = $badgeClasses[$row->task_type] ?? 'bg-label-secondary';
                $taskTypeName = Task::TASK_TYPES[$row->task_type] ?? 'غير محدد';
                return '<span class="badge ' . $class . '">' . e($taskTypeName) . '</span>';
            })
            ->orderColumn('customer_name', 'c.name $1')
            ->orderColumn('account_number', 'c.account_number $1')
            ->orderColumn('identity_number', 'c.identity_number $1')
            ->orderColumn('company_name', 'cmp.name $1')
            ->orderColumn('portfolio_name', 'prt.name $1')
            ->orderColumn('remaining_debt', 'remaining_debt $1')
            ->orderColumn('follow_result', 'tasks.task_type $1')
            ->filterColumn('customer_name', fn($q, $k) => $q->where('c.name', 'like', "%{$k}%"))
            ->filterColumn('account_number', fn($q, $k) => $q->where('c.account_number', 'like', "%{$k}%"))
            ->filterColumn('identity_number', fn($q, $k) => $q->where('c.identity_number', 'like', "%{$k}%"))
            ->filterColumn('company_name', fn($q, $k) => $q->where('cmp.name', 'like', "%{$k}%"))
            ->filterColumn('portfolio_name', fn($q, $k) => $q->where('prt.name', 'like', "%{$k}%"))
            ->rawColumns(['customer_name', 'follow_result'])
            ->make(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Sessions - Full Page
    |--------------------------------------------------------------------------
    | صفحة منفصلة لعرض جلسات التحصيل اليومية بكامل الفلاتر
    | الجدول (بيانات + عرض) يُدار بالكامل عبر CollectionSessionsDataTable
    */
    public function collectionSessionsPage(CollectionSessionsDataTable $dataTable)
    {
        return $dataTable->render('tenants.dashboard.admin.collection-sessions');
    }

    /*
    |--------------------------------------------------------------------------
    | Excellence Dashboard
    |--------------------------------------------------------------------------
    */
    public function excellence(Request $request)
    {
        if ($request->ajax()) {
            $period = $request->input('period', 'month');

            try {
                $data = $this->dashboardService->getExcellenceDashboardData($period);

                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'period' => $period
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ في جلب البيانات',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                    'data' => [
                        'topPerformers' => [],
                        'performanceDistribution' => [],
                        'performanceTrend' => []
                    ]
                ], 500);
            }
        }
        return $this->adminDashboard();
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function exportCollectionSessions(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $status = $request->input('status');

        $fileName = 'جلسات_التحصيل_' . $date . '.xlsx';

        return Excel::download(
            new CollectionSessionsExport($date, $status),
            $fileName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Performance Comparison - For User Dashboard
    |--------------------------------------------------------------------------
    */
    public function getMyPerformanceComparison(Request $request)
    {
        if (!$request->ajax() && !$request->wantsJson()) {
            abort(404);
        }

        $period = $request->input('period', 'month');
        $dataType = $request->input('dataType', 'collections');
        $userId = Auth::id();

        try {
            $data = $this->dashboardService->getUserComparisonData($userId, $period, $dataType);

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period,
                'dataType' => $dataType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get Statistics Data
    |--------------------------------------------------------------------------
    */
    public function getStatisticsData(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        try {
            $data = $this->dashboardService->getStatisticsData($period);

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات الإحصائيات',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Approvals Data
    |--------------------------------------------------------------------------
    */
    public function getApprovalsData(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        try {
            $data = $this->dashboardService->getApprovalsData($period);

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات الاعتمادات',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get Overdue Tasks - Admin
    |--------------------------------------------------------------------------
    */
    public function getOverdueTasks(Request $request): JsonResponse
    {
        // ✅ جلب User IDs المسموح بها حسب صلاحيات المستخدم
        $accessibleUserIds = $this->getAccessibleUserIdsForDataTables();

        $filters = [
            'company_id' => $request->input('company_id'),
            'portfolio_id' => $request->input('portfolio_id'),
            'relationship_manager_id' => $request->input('relationship_manager_id'),
            'result_type' => $request->input('result_type'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $remainingDebtExpr = '(SELECT
        COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
        FROM customer_transactions
        WHERE customer_id = tasks.customer_id)';

        $query = Task::select(
            'tasks.*',
            'c.name as customer_name',
            'c.phone as customer_phone',
            'c.account_number',
            'cmp.name as company_name',
            'prt.name as portfolio_name',
            'rm.name as relationship_manager_name',
            'rm.employee_number as relationship_manager_emp',
            DB::raw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) FROM customer_transactions WHERE customer_id = tasks.customer_id) as remaining_debt'),
            DB::raw('DATEDIFF(CURDATE(), tasks.due_date) as overdue_days')
        )
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'c.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'c.portfolio_id')
            ->leftJoin('users as rm', 'rm.id', '=', 'c.relationship_manager_id')
            ->where('tasks.status', 'pending')
            ->whereDate('tasks.due_date', '<', now()->toDateString())
            ->whereNull('c.deleted_at')
            ->whereRaw("$remainingDebtExpr > 0");

        // ✅ تطبيق فلترة الفريق/الصلاحيات تلقائياً
        if ($accessibleUserIds !== null) {
            $query->whereIn('c.relationship_manager_id', $accessibleUserIds);
        }

        // ✅ تطبيق الفلاتر الاختيارية من الـ Request
        if (!empty($filters['company_id'])) {
            $query->where('c.company_id', $filters['company_id']);
        }

        if (!empty($filters['portfolio_id'])) {
            $query->where('c.portfolio_id', $filters['portfolio_id']);
        }

        if (!empty($filters['relationship_manager_id'])) {
            $query->where('c.relationship_manager_id', $filters['relationship_manager_id']);
        }

        if (!empty($filters['result_type'])) {
            $query->where('tasks.task_type', $filters['result_type']);
        }

        if (!empty($filters['overdue_days'])) {
            $query->whereRaw('DATEDIFF(CURDATE(), tasks.due_date) <= ?', [$filters['overdue_days']]);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('tasks.due_date', [$filters['date_from'], $filters['date_to']]);
        }

        // ترتيب النتائج
        $query->orderByRaw('overdue_days DESC')
            ->orderBy('rm.name', 'ASC')
            ->orderByRaw('remaining_debt DESC');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($row) {
                $showUrl = tenant_route_name('customers.show', $row->customer_id);
                $phone = $row->customer_phone ?: 'غير محدد';
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div class="flex-grow-1 min-w-0">'
                    . '<a href="' . $showUrl . '" class="text-decoration-none text-dark fw-semibold">'
                    . e($row->customer_name) . '</a>'
                    . '<div class="small text-muted">' . e($phone) . '</div>'
                    . '</div></div>';
            })
            ->editColumn('account_number', fn($row) => e($row->account_number ?: 'غير محدد'))
            ->addColumn('company_name', fn($row) => e($row->company_name ?: 'غير محدد'))
            ->addColumn('portfolio_name', fn($row) => e($row->portfolio_name ?: 'غير محدد'))
            ->addColumn('task_type', function ($row) {
                $badgeClasses = [
                    'promise' => ['label' => 'وعد بالسداد', 'class' => 'bg-label-success'],
                    'callback' => ['label' => 'معاودة اتصال', 'class' => 'bg-label-primary'],
                    'document_request' => ['label' => 'طلب مستندات', 'class' => 'bg-label-warning'],
                ];
                $badge = $badgeClasses[$row->task_type] ?? ['label' => 'غير محدد', 'class' => 'bg-label-secondary'];
                return '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
            })
            ->addColumn('scheduled_date', fn($row) => $row->due_date->format('Y-m-d'))
            ->addColumn('overdue_days', function ($row) {
                $days = (int)$row->overdue_days;
                if ($days <= 3) {
                    $class = 'bg-label-warning';
                    $text = $days . ' يوم';
                } elseif ($days <= 7) {
                    $class = 'bg-label-danger';
                    $text = $days . ' أيام';
                } else {
                    $class = 'bg-danger text-white';
                    $text = $days . ' يوم';
                }
                return '<span class="badge ' . $class . ' fw-bold">' . $text . '</span>';
            })
            ->addColumn('remaining_debt', fn($row) => number_format((float)$row->remaining_debt, 2) . ' ريال')

            ->addColumn('relationship_manager', function ($row) {
                $name = $row->relationship_manager_name ?: 'غير محدد';
                $emp = $row->relationship_manager_emp ?: 'غير محدد';
                return '<div class="text-center">'
                    . '<div class="fw-semibold">' . e($name) . '</div>'
                    . '<div class="small text-muted">' . e($emp) . '</div>'
                    . '</div>';
            })
            ->orderColumn('customer_name', 'c.name $1')
            ->orderColumn('account_number', 'c.account_number $1')
            ->orderColumn('company_name', 'cmp.name $1')
            ->orderColumn('portfolio_name', 'prt.name $1')
            ->orderColumn('task_type', 'tasks.task_type $1')
            ->orderColumn('scheduled_date', 'tasks.due_date $1')
            ->orderColumn('overdue_days', 'overdue_days $1')
            ->orderColumn('remaining_debt', 'remaining_debt $1')
            ->orderColumn('relationship_manager', 'rm.name $1')
            ->filterColumn('customer_name', fn($q, $k) => $q->where('c.name', 'like', "%{$k}%"))
            ->filterColumn('account_number', fn($q, $k) => $q->where('c.account_number', 'like', "%{$k}%"))
            ->filterColumn('company_name', fn($q, $k) => $q->where('cmp.name', 'like', "%{$k}%"))
            ->filterColumn('portfolio_name', fn($q, $k) => $q->where('prt.name', 'like', "%{$k}%"))
            ->filterColumn('relationship_manager', fn($q, $k) => $q->where('rm.name', 'like', "%{$k}%"))
            ->rawColumns(['customer_name', 'task_type', 'overdue_days', 'relationship_manager'])
            ->make(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Overdue Tasks - User
    |--------------------------------------------------------------------------
    */
    public function getMyOverdueTasks(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $filters = [
            'result_type'  => $request->input('result_type'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from'    => $request->input('date_from'),
            'date_to'      => $request->input('date_to'),
        ];

        // ✅ Expressions ثابتة (لا تعتمد على alias)
        $remainingDebtExpr = '(SELECT
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
           FROM customer_transactions
           WHERE customer_id = tasks.customer_id)';

        $overdueDaysExpr = 'DATEDIFF(CURDATE(), tasks.due_date)';

        $query = Task::query()
            ->select(
                'tasks.*',
                'c.name as customer_name',
                'c.phone as customer_phone',
                'c.account_number',
                'cmp.name as company_name',
                'prt.name as portfolio_name',
                DB::raw("$remainingDebtExpr as remaining_debt"),
                DB::raw("$overdueDaysExpr as overdue_days")
            )
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'c.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'c.portfolio_id')
            ->where('tasks.status', 'pending')
            ->whereDate('tasks.due_date', '<', now()->toDateString())
            ->where('c.relationship_manager_id', $userId)
            ->whereNull('c.deleted_at')
            ->whereRaw("$remainingDebtExpr > 0");

        // ✅ تطبيق الفلاتر
        if (!empty($filters['result_type'])) {
            $query->where('tasks.task_type', $filters['result_type']);
        }

        if (!empty($filters['overdue_days'])) {
            // حسب كودك الحالي: "أقل أو يساوي"
            $query->whereRaw("$overdueDaysExpr <= ?", [(int)$filters['overdue_days']]);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('tasks.due_date', [$filters['date_from'], $filters['date_to']]);
        }

        // ✅ ترتيب افتراضي ثابت (حتى لو فيه تعادل)
        $query->orderByRaw("$overdueDaysExpr DESC")
            ->orderBy('tasks.due_date', 'asc')
            ->orderBy('tasks.created_at', 'desc')
            ->orderBy('tasks.id', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()

            ->addColumn('customer_name', function ($row) {
                $showUrl = tenant_route_name('customers.show', $row->customer_id);
                $phone = $row->customer_phone ?: 'غير محدد';
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div class="flex-grow-1 min-w-0">'
                    . '<a href="' . $showUrl . '" class="text-decoration-none text-dark fw-semibold">'
                    . e($row->customer_name) . '</a>'
                    . '<div class="small text-muted">' . e($phone) . '</div>'
                    . '</div></div>';
            })

            ->editColumn('account_number', fn($row) => e($row->account_number ?: 'غير محدد'))
            ->addColumn('company_name', fn($row) => e($row->company_name ?: 'غير محدد'))
            ->addColumn('portfolio_name', fn($row) => e($row->portfolio_name ?: 'غير محدد'))

            ->addColumn('task_type', function ($row) {
                $badgeClasses = [
                    'promise'           => ['label' => 'وعد بالسداد',    'class' => 'bg-label-success'],
                    'callback'          => ['label' => 'معاودة اتصال',   'class' => 'bg-label-primary'],
                    'document_request'  => ['label' => 'طلب مستندات',    'class' => 'bg-label-warning'],
                ];
                $badge = $badgeClasses[$row->task_type] ?? ['label' => 'غير محدد', 'class' => 'bg-label-secondary'];
                return '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
            })

            ->addColumn('scheduled_date', fn($row) => optional($row->due_date)->format('Y-m-d'))

            ->addColumn('overdue_days', function ($row) {
                $days = (int) $row->overdue_days;

                if ($days <= 3) {
                    $class = 'bg-label-warning';
                    $text  = $days . ' يوم';
                } elseif ($days <= 7) {
                    $class = 'bg-label-danger';
                    $text  = $days . ' أيام';
                } else {
                    $class = 'bg-danger text-white';
                    $text  = $days . ' يوم';
                }

                return '<span class="badge ' . $class . ' fw-bold">' . $text . '</span>';
            })

            ->addColumn('remaining_debt', fn($row) => number_format((float)$row->remaining_debt, 2) . ' ريال')

            // ✅ ترتيب الأعمدة (الأهم: overdue_days و remaining_debt)
            ->orderColumn('customer_name', 'c.name $1')
            ->orderColumn('account_number', 'c.account_number $1')
            ->orderColumn('company_name', 'cmp.name $1')
            ->orderColumn('portfolio_name', 'prt.name $1')
            ->orderColumn('task_type', 'tasks.task_type $1')
            ->orderColumn('scheduled_date', 'tasks.due_date $1')

            // ✅ هنا التعديل المهم: ترتيب بالتعبير + Tie-breakers ثابتة
            ->orderColumn('overdue_days', function ($q, $order) use ($overdueDaysExpr) {
                $q->orderByRaw("$overdueDaysExpr $order")
                    ->orderBy('tasks.due_date', 'asc')
                    ->orderBy('tasks.created_at', 'desc')
                    ->orderBy('tasks.id', 'desc');
            })

            ->orderColumn('remaining_debt', function ($q, $order) use ($remainingDebtExpr, $overdueDaysExpr) {
                $q->orderByRaw("$remainingDebtExpr $order")
                    ->orderByRaw("$overdueDaysExpr DESC")
                    ->orderBy('tasks.due_date', 'asc')
                    ->orderBy('tasks.created_at', 'desc')
                    ->orderBy('tasks.id', 'desc');
            })

            // ✅ الفلاتر للبحث
            ->filterColumn('customer_name', fn($q, $k) => $q->where('c.name', 'like', "%{$k}%"))
            ->filterColumn('account_number', fn($q, $k) => $q->where('c.account_number', 'like', "%{$k}%"))
            ->filterColumn('company_name', fn($q, $k) => $q->where('cmp.name', 'like', "%{$k}%"))
            ->filterColumn('portfolio_name', fn($q, $k) => $q->where('prt.name', 'like', "%{$k}%"))

            ->rawColumns(['customer_name', 'task_type', 'overdue_days'])
            ->make(true);
    }


    /*
    |--------------------------------------------------------------------------
    | Export Overdue Tasks - Admin
    |--------------------------------------------------------------------------
    */
    public function exportOverdueTasks(Request $request)
    {
        $filters = [
            'company_id' => $request->input('company_id'),
            'portfolio_id' => $request->input('portfolio_id'),
            'relationship_manager_id' => $request->input('relationship_manager_id'),
            'result_type' => $request->input('result_type'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $fileName = 'المهام_المتأخرة_' . now()->format('Y-m-d_H-i') . '.xlsx';

        return Excel::download(
            new OverdueTasksExport(null, $filters),
            $fileName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Export My Overdue Tasks - User
    |--------------------------------------------------------------------------
    */
    public function exportMyOverdueTasks(Request $request)
    {
        $userId = Auth::id();

        $filters = [
            'company_id' => $request->input('company_id'),
            'portfolio_id' => $request->input('portfolio_id'),
            'result_type' => $request->input('result_type'),
            'overdue_days' => $request->input('overdue_days'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $fileName = 'مهامي_المتأخرة_' . now()->format('Y-m-d_H-i') . '.xlsx';

        return Excel::download(
            new OverdueTasksExport($userId, $filters),
            $fileName
        );
    }

    private function dummyDataTableResponse(Request $request, array $rows): JsonResponse
    {
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => count($rows),
            'recordsFiltered' => count($rows),
            'data' => $rows,
        ]);
    }

    private function dummyUpcomingFollowUps(): array
    {
        return [
            [
                'DT_RowIndex' => 1,
                'customer_name' => '<a href="#" class="text-decoration-none text-dark">عميل تجريبي</a><div class="small text-muted">0500000000</div>',
                'account_number' => 'ACC-1001',
                'identity_number' => '1000000001',
                'company_name' => 'شركة تجريبية',
                'portfolio_name' => 'محفظة تجريبية',
                'follow_result' => '<span class="badge bg-label-success">وعد بالسداد</span>',
                'remaining_debt' => '12,500.00 ريال',
                'relationship_manager' => '<div class="text-center"><div class="fw-semibold">مسؤول تجريبي</div><div class="small text-muted">EMP-001</div></div>',
            ],
            [
                'DT_RowIndex' => 2,
                'customer_name' => '<a href="#" class="text-decoration-none text-dark">عميل نموذجي</a><div class="small text-muted">0550000000</div>',
                'account_number' => 'ACC-1002',
                'identity_number' => '1000000002',
                'company_name' => 'شركة نموذجية',
                'portfolio_name' => 'محفظة نموذجية',
                'follow_result' => '<span class="badge bg-label-primary">معاودة اتصال</span>',
                'remaining_debt' => '8,750.00 ريال',
                'relationship_manager' => '<div class="text-center"><div class="fw-semibold">مسؤول نموذجي</div><div class="small text-muted">EMP-002</div></div>',
            ],
        ];
    }

    private function dummyOverdueTasks(): array
    {
        return [
            [
                'DT_RowIndex' => 1,
                'customer_name' => '<a href="#" class="text-decoration-none text-dark fw-semibold">عميل تجريبي</a><div class="small text-muted">0500000000</div>',
                'account_number' => 'ACC-1001',
                'company_name' => 'شركة تجريبية',
                'portfolio_name' => 'محفظة تجريبية',
                'task_type' => '<span class="badge bg-label-warning">طلب مستندات</span>',
                'scheduled_date' => now()->subDays(3)->format('Y-m-d'),
                'overdue_days' => '<span class="badge bg-label-warning fw-bold">3 أيام</span>',
                'remaining_debt' => '12,500.00 ريال',
                'relationship_manager' => '<div class="text-center"><div class="fw-semibold">مسؤول تجريبي</div><div class="small text-muted">EMP-001</div></div>',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Accessible User IDs for Current User
    |--------------------------------------------------------------------------
    */
    private function getAccessibleUserIdsForDataTables(): ?array
    {
        $user = Auth::user();
        if ($user->canManageTeam()) {
            $ids = $user->teamMemberIds();
            return $ids;
        }

        if ($user->can('لوحة تحكم الإدارة')) {
            return null;
        }
        return [$user->id];
    }
}

<?php

namespace App\Services\Tenants\FinancialCollection\CollectionProcess;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Services\Tenants\BusinessCenter\Customer\Follow\CustomerFollowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionProcessService
{
    /*
    |--------------------------------------------------------------------------
    | الحصول على العميل التالي للموظف
    |--------------------------------------------------------------------------
    */
    public function getNextCustomerForUser(): ?Customer
    {
        $userId = Auth::id();

        // أولاً: العملاء الذين لديهم مهام اليوم
        $todayTasksCustomers = $this->getTodayTasksCustomers($userId);
        if ($todayTasksCustomers->isNotEmpty()) {
            return $todayTasksCustomers->first();
        }

        // ثانياً: العملاء الذين لديهم مهام متأخرة
        $overdueTasksCustomers = $this->getOverdueTasksCustomers($userId);
        if ($overdueTasksCustomers->isNotEmpty()) {
            return $overdueTasksCustomers->first();
        }

        // ثالثاً: العملاء الجدد
        $newCustomer = $this->getNewCustomer($userId);
        if ($newCustomer && $this->hasRemainingDebt($newCustomer->id)) {
            return $newCustomer;
        }

        // رابعاً: العملاء المؤجلين
        $deferredCustomer = $this->getDeferredCustomer($userId);
        if ($deferredCustomer && $this->hasRemainingDebt($deferredCustomer->id)) {
            return $deferredCustomer;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على العملاء الذين لديهم مهام اليوم
    |--------------------------------------------------------------------------
    */
    protected function getTodayTasksCustomers(int $userId): EloquentCollection
    {
        $today = now()->toDateString();

        $debitSub = CustomerTransaction::query()
            ->selectRaw('COALESCE(SUM(amount),0)')
            ->whereColumn('customer_id', 'customers.id')
            ->where('type', 'debit');

        $creditSub = CustomerTransaction::query()
            ->selectRaw('COALESCE(SUM(amount),0)')
            ->whereColumn('customer_id', 'customers.id')
            ->where('type', 'credit');

        $base = Customer::query()
            ->where('relationship_manager_id', $userId)
            ->whereHas('tasks', function ($q) use ($today) {
                $q->where('status', 'pending')
                    ->whereDate('due_date', $today);
            })
            ->select('customers.*')
            ->selectSub($debitSub, 'total_debit')
            ->selectSub($creditSub, 'total_credit');

        $rows = DB::query()
            ->fromSub($base, 'c')
            ->select('c.id')
            ->whereRaw('(c.total_debit - c.total_credit) > 0')
            ->orderByDesc(DB::raw('(c.total_debit - c.total_credit)'))
            ->get();

        $ids = $rows->pluck('id')->values();

        if ($ids->isEmpty()) {
            return new EloquentCollection();
        }

        $idsCsv = $ids->implode(',');

        $customers = Customer::with('portfolio:id,name,call_start_time,call_end_time')
            ->whereIn('id', $ids)
            ->orderByRaw("FIELD(id, $idsCsv)")
            ->get();

        return $customers->filter(function ($customer) {
            return $this->isWithinCallTime($customer);
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على العملاء الذين لديهم مهام متأخرة
    |--------------------------------------------------------------------------
    */
    protected function getOverdueTasksCustomers(int $userId): EloquentCollection
    {
        $today = now()->toDateString();

        $debitSub = CustomerTransaction::query()
            ->selectRaw('COALESCE(SUM(amount),0)')
            ->whereColumn('customer_id', 'customers.id')
            ->where('type', 'debit');

        $creditSub = CustomerTransaction::query()
            ->selectRaw('COALESCE(SUM(amount),0)')
            ->whereColumn('customer_id', 'customers.id')
            ->where('type', 'credit');

        $maxOverdueDaysSub = Task::query()
            ->selectRaw('MAX(DATEDIFF(CURDATE(), due_date))')
            ->whereColumn('customer_id', 'customers.id')
            ->where('status', 'pending')
            ->whereDate('due_date', '<', $today);

        $base = Customer::query()
            ->where('relationship_manager_id', $userId)
            ->whereHas('tasks', function ($q) use ($today) {
                $q->where('status', 'pending')
                    ->whereDate('due_date', '<', $today);
            })
            ->select('customers.*')
            ->selectSub($debitSub, 'total_debit')
            ->selectSub($creditSub, 'total_credit')
            ->selectSub($maxOverdueDaysSub, 'max_overdue_days');

        $rows = DB::query()
            ->fromSub($base, 'c')
            ->select('c.id')
            ->whereRaw('(c.total_debit - c.total_credit) > 0')
            ->orderByDesc('c.max_overdue_days')
            ->orderByDesc(DB::raw('(c.total_debit - c.total_credit)'))
            ->get();

        $ids = $rows->pluck('id')->values();

        if ($ids->isEmpty()) {
            return new EloquentCollection();
        }

        $idsCsv = $ids->implode(',');

        $customers = Customer::with('portfolio:id,name,call_start_time,call_end_time')
            ->whereIn('id', $ids)
            ->orderByRaw("FIELD(id, $idsCsv)")
            ->get();

        return $customers->filter(function ($customer) {
            return $this->isWithinCallTime($customer);
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function hasAnyCustomers(int $userId): bool
    {
        return Customer::where('relationship_manager_id', $userId)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | التنقل بين العملاء
    |--------------------------------------------------------------------------
    */
    public function navigateToCustomer(string $direction, ?int $currentCustomerId = null): ?Customer
    {
        $userId = Auth::id();

        $todayTasksCustomers   = $this->getTodayTasksCustomers($userId);
        $overdueTasksCustomers = $this->getOverdueTasksCustomers($userId);
        $newCustomers          = $this->getNewCustomers($userId);
        $deferredCustomers     = $this->getDeferredCustomers($userId);

        //  دمج + إزالة التكرار بين القوائم مع تثبيت أولوية أول ظهور
        $allCustomers = $todayTasksCustomers
            ->merge($overdueTasksCustomers)
            ->merge($newCustomers)
            ->merge($deferredCustomers)
            ->unique('id')
            ->values();

        // تصفية العملاء الذين لديهم مديونية
        $customersWithDebt = $allCustomers->filter(fn($c) => $this->hasRemainingDebt($c->id))->values();

        if ($customersWithDebt->isEmpty()) return null;

        if (!$currentCustomerId) return $customersWithDebt->first();

        $currentIndex = $customersWithDebt->search(fn($c) => $c->id === $currentCustomerId);
        if ($currentIndex === false) return $customersWithDebt->first();

        $count = $customersWithDebt->count();
        $nextIndex = $direction === 'next'
            ? ($currentIndex + 1) % $count
            : ($currentIndex - 1 + $count) % $count;

        return $customersWithDebt->get($nextIndex);
    }

    /*
    |--------------------------------------------------------------------------
    | تخطي العميل الحالي
    |--------------------------------------------------------------------------
    */
    public function skipCustomer(Customer $customer): ?Customer
    {
        return $this->navigateToCustomer('next', $customer->id);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على تفاصيل العميل
    |--------------------------------------------------------------------------
    */
    public function getCustomerDetails(Customer $customer): array
    {
        $customer->load([
            'company:id,name,logo_path',
            'portfolio:id,name',
            'nationalityCountry:id,name',
            'relationshipManager:id,name',
            'customerStatus:id,name',
            'lastContactResult:id,name',
            'phones',
            'dynamicValues.portfolioField',
            'notes',
            'customerFollows' => function ($query) {
                $query->with([
                    'contactMethod:id,name',
                    'contactResult:id,name',
                    'createdBy:id,name',
                    'fullPaymentDetails',
                    'partialPaymentDetails',
                    'promisePaymentDetails',
                    'callbackDetails',
                    'documentRequestDetails',
                    'objectionDetails',
                    'recoveryDetails'
                ])->latest()->limit(5);
            },
            // إضافة تحميل المهام المتعلقة بالعميل
            'tasks' => function ($query) {
                $query->where('status', 'pending')
                    ->orderBy('due_date', 'asc');
            }
        ]);

        $customer->loadSum(['transactions as total_debit' => function ($query) {
            $query->where('type', 'debit');
        }], 'amount');

        $customer->loadSum(['transactions as total_credit' => function ($query) {
            $query->where('type', 'credit');
        }], 'amount');

        // جلب جميع الحسابات المرتبطة برقم الهوية
        $allAccounts = Customer::where('identity_number', $customer->identity_number)
            ->with([
                'company:id,name,logo_path',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'customerStatus:id,name',
                'phones',
                'dynamicValues.portfolioField'
            ])
            ->addSelect([
                '*',
                'total_debit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('type', 'debit'),
                'total_credit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('type', 'credit'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // حساب الإحصائيات الشاملة
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($allAccounts as $account) {
            $totalDebit += $account->total_debit ?? 0;
            $totalCredit += $account->total_credit ?? 0;
        }

        $comprehensiveStats = [
            'total_accounts' => $allAccounts->count(),
            'total_debt' => $totalDebit,
            'total_paid' => $totalCredit,
            'total_remaining' => ($totalDebit - $totalCredit),
        ];

        // التحقق من وجود مهام اليوم
        $hasTodayTasks = $customer->tasks()
            ->where('status', 'pending')
            ->whereDate('due_date', now()->toDateString())
            ->exists();

        // التحقق من وجود مهام متأخرة
        $hasOverdueTasks = $customer->tasks()
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->exists();

        return [
            'customer' => $customer,
            'remaining_debt' => ($customer->total_debit ?? 0) - ($customer->total_credit ?? 0),
            'last_contact' => $customer->customerFollows->first(),
            'next_action' => $this->getNextAction($customer),
            'priority_level' => $this->getPriorityLevel($customer),
            'dynamic_fields' => $this->getDynamicFieldsForDisplay($customer),
            'all_accounts' => $allAccounts,
            'comprehensive_stats' => $comprehensiveStats,
            'has_today_tasks' => $hasTodayTasks,
            'has_overdue_tasks' => $hasOverdueTasks,
            'tasks' => $customer->tasks,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الدوال المساعدة الخاصة
    |--------------------------------------------------------------------------
    */
    protected function getPriorityCustomer(int $userId): ?Customer
    {
        return $this->getTodayTasksCustomers($userId)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Get New Customer
    |--------------------------------------------------------------------------
    */
    protected function getNewCustomer(int $userId): ?Customer
    {
        $customers = Customer::with('portfolio:id,name,call_start_time,call_end_time')
            ->where('relationship_manager_id', $userId)
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
               FROM customer_transactions
               WHERE customer_id = customers.id) -
               (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
               FROM customer_transactions
               WHERE customer_id = customers.id) > 0')
            ->whereDoesntHave('customerFollows')
            ->orderByRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) -
                 (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) DESC')
            ->limit(50)
            ->get();

        //  فلترة وإرجاع أول عميل متاح
        return $customers->filter(function ($customer) {
            return $this->isWithinCallTime($customer);
        })->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Deferred Customer
    |--------------------------------------------------------------------------
    */
    protected function getDeferredCustomer(int $userId): ?Customer
    {
        $customers = Customer::with('portfolio:id,name,call_start_time,call_end_time')
            ->where('relationship_manager_id', $userId)
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) -
                (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) > 0')
            ->whereHas('latestFollow', function ($query) {
                $query->whereIn('contact_result_id', [5, 6, 7, 8])
                    ->whereDate('created_at', '<', Carbon::today());
            })
            ->whereIn('last_contact_result_id', [5, 6, 7, 8])
            ->orderBy(
                CustomerFollow::select('created_at')
                    ->whereColumn('customer_id', 'customers.id')
                    ->latest()
                    ->limit(1),
                'asc'
            )
            ->limit(50)
            ->get();

        //  فلترة وإرجاع أول عميل متاح
        return $customers->filter(function ($customer) {
            return $this->isWithinCallTime($customer);
        })->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Customers By Priority
    |--------------------------------------------------------------------------
    */
    protected function getAllCustomersByPriority(int $userId)
    {
        $todayTasksCustomers = $this->getTodayTasksCustomers($userId);
        $overdueTasksCustomers = $this->getOverdueTasksCustomers($userId);
        $newCustomers = $this->getNewCustomers($userId);
        $deferredCustomers = $this->getDeferredCustomers($userId);

        return $todayTasksCustomers
            ->merge($overdueTasksCustomers)
            ->merge($newCustomers)
            ->merge($deferredCustomers);
    }

    /*
    |--------------------------------------------------------------------------
    | Get New Customers
    |--------------------------------------------------------------------------
    */
    protected function getNewCustomers(int $userId)
    {
        $customers = Customer::with('portfolio:id,name,call_start_time,call_end_time')
            ->where('relationship_manager_id', $userId)
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
               FROM customer_transactions
               WHERE customer_id = customers.id) >
               (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
               FROM customer_transactions
               WHERE customer_id = customers.id)')
            ->whereDoesntHave('customerFollows')
            ->orderByRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) -
                 (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) DESC')
            ->get();

        //  فلترة العملاء حسب أوقات الاتصال المسموحة
        return $customers->filter(function ($customer) {
            return $this->isWithinCallTime($customer);
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Deferred Customers
    |--------------------------------------------------------------------------
    */
    protected function getDeferredCustomers(int $userId)
    {
        $customers = Customer::with('portfolio:id,name,call_start_time,call_end_time')
            ->where('relationship_manager_id', $userId)
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id) >
                (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_id = customers.id)')
            ->whereHas('latestFollow', function ($query) {
                $query->whereIn('contact_result_id', [5, 6, 7, 8])
                    ->whereDate('created_at', '<', Carbon::today());
            })
            ->whereIn('last_contact_result_id', [5, 6, 7, 8])
            ->orderBy(
                CustomerFollow::select('created_at')
                    ->whereColumn('customer_id', 'customers.id')
                    ->latest()
                    ->limit(1),
                'asc'
            )
            ->get();

        //  فلترة العملاء حسب أوقات الاتصال المسموحة
        return $customers->filter(function ($customer) {
            return $this->isWithinCallTime($customer);
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Has Remaining Debt
    |--------------------------------------------------------------------------
    */
    public function hasRemainingDebt(int $customerId): bool
    {
        $totalDebit = CustomerTransaction::where('customer_id', $customerId)
            ->where('type', 'debit')
            ->sum('amount');

        $totalCredit = CustomerTransaction::where('customer_id', $customerId)
            ->where('type', 'credit')
            ->sum('amount');

        return ($totalDebit - $totalCredit) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Next Action
    |--------------------------------------------------------------------------
    */
    protected function getNextAction(Customer $customer): ?string
    {
        // التحقق من وجود مهام اليوم
        $todayTask = $customer->tasks()
            ->where('status', 'pending')
            ->whereDate('due_date', now()->toDateString())
            ->orderBy('due_date', 'asc')
            ->first();

        if ($todayTask) {
            $taskTypeName = Task::TASK_TYPES[$todayTask->task_type] ?? 'غير محدد';
            return 'مهمة اليوم: ' . $taskTypeName;
        }

        // التحقق من وجود مهام متأخرة
        $overdueTask = $customer->tasks()
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date', 'asc')
            ->first();

        if ($overdueTask) {
            $taskTypeName = Task::TASK_TYPES[$overdueTask->task_type] ?? 'غير محدد';
            $overdueDays = now()->diffInDays($overdueTask->due_date);
            return 'مهمة متأخرة (' . $overdueDays . ' أيام): ' . $taskTypeName;
        }

        // إذا لم توجد مهام، استخدم المنطق القديم
        $lastFollow = $customer->customerFollows->first();

        if (!$lastFollow) {
            return 'متابعة جديدة';
        }

        switch ($lastFollow->contact_result_id) {
            case 3:
                return 'متابعة وعد السداد';
            case 4:
                return 'معاودة الاتصال';
            case 9:
                return 'متابعة طلب المستندات';
            default:
                return 'متابعة عادية';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Priority Level
    |--------------------------------------------------------------------------
    */
    protected function getPriorityLevel(Customer $customer): string
    {
        $debt = ($customer->total_debit ?? 0) - ($customer->total_credit ?? 0);

        if ($debt >= 100000) {
            return 'عالية';
        } elseif ($debt >= 50000) {
            return 'متوسطة';
        } else {
            return 'منخفضة';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Dynamic Fields For Display
    |--------------------------------------------------------------------------
    */
    private function getDynamicFieldsForDisplay(Customer $customer): array
    {
        if (!$customer->portfolio) {
            return [];
        }

        $portfolioFields = $customer->portfolio->portfolioFields ?? collect();
        $dynamicValues = $customer->dynamicValues->keyBy('portfolio_field_id');

        return $portfolioFields->map(function ($field) use ($dynamicValues) {
            $value = $dynamicValues->get($field->id)?->value ?? 'غير محدد';
            return [
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'value' => $value
            ];
        })->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من أن العميل ضمن وقت الاتصال المسموح
    |--------------------------------------------------------------------------
    */
    protected function isWithinCallTime(Customer $customer): bool
    {
        $portfolio = $customer->portfolio;

        if (!$portfolio) {
            return true;
        }

        $callStartTime = $portfolio->call_start_time;
        $callEndTime = $portfolio->call_end_time;

        if ($callStartTime) {
            if (strlen($callStartTime) > 8) {
                $callStartTime = date('H:i:s', strtotime($callStartTime));
            }
        }

        if ($callEndTime) {
            if (strlen($callEndTime) > 8) {
                $callEndTime = date('H:i:s', strtotime($callEndTime));
            }
        }

        if (!$callStartTime && !$callEndTime) {
            return true;
        }

        if (!$callStartTime || !$callEndTime) {
            return false;
        }

        $currentTime = now()->format('H:i:s');

        return $this->isTimeInRange($currentTime, $callStartTime, $callEndTime);
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من وقوع الوقت الحالي ضمن النطاق (مع دعم الأوقات الليلية)
    |--------------------------------------------------------------------------
    */
    protected function isTimeInRange(string $current, string $start, string $end): bool
    {
        if ($start <= $end) {
            // نطاق عادي: 09:00 - 17:00
            return $current >= $start && $current <= $end;
        } else {
            // نطاق ليلي يعبر منتصف الليل: 22:00 - 06:00
            return $current >= $start || $current <= $end;
        }
    }



    // عدد جميع العملاء المتبقين
    public function getRemainingCustomersCount(): int
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        //  Query واحدة فقط - بدون load البيانات
        return Customer::where('relationship_manager_id', $userId)
            ->where(function ($query) use ($today) {
                $query
                    // عملاء مع مهام اليوم
                    ->whereHas('tasks', function ($q) use ($today) {
                        $q->where('status', 'pending')
                            ->whereDate('due_date', $today);
                    })
                    // أو عملاء مع مهام متأخرة
                    ->orWhereHas('tasks', function ($q) use ($today) {
                        $q->where('status', 'pending')
                            ->whereDate('due_date', '<', $today);
                    })
                    // أو عملاء جدد بدون متابعات
                    ->orWhereDoesntHave('customerFollows')
                    // أو عملاء مؤجلين
                    ->orWhere(function ($q) use ($today) {
                        $q->whereHas('latestFollow', function ($qf) use ($today) {
                            $qf->whereIn('contact_result_id', [5, 6, 7, 8])
                                ->whereDate('created_at', '<', $today);
                        })
                            ->whereIn('last_contact_result_id', [5, 6, 7, 8]);
                    });
            })
            //  فلتر المديونية
            ->whereRaw('(SELECT COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
             FROM customer_transactions
             WHERE customer_id = customers.id) -
            (SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
             FROM customer_transactions
             WHERE customer_id = customers.id) > 0')->distinct()->count();
    }
}

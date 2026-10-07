<?php

namespace App\Services\Tenants\FinancialCollection\Collection;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Follow\FullPaymentDetails;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Notification\NotificationService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CollectionService
{

    /*
    |--------------------------------------------------------------------------
    | Protected Properties
    |--------------------------------------------------------------------------
    */
    protected NotificationService $notificationService;

    /*
    |--------------------------------------------------------------------------
    | Construct
    |--------------------------------------------------------------------------
    */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Collections Query
    |--------------------------------------------------------------------------
    | جلب استعلام تحصيلاتي للمستخدم الحالي فقط.
    */
    public function getMyCollectionsQuery(): Builder
    {
        return $this->getCollectionsQuery()
            ->where('added_by', auth('tenant')->id());
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Collection Statistics
    |--------------------------------------------------------------------------
    | جلب إحصائيات تحصيلاتي للمستخدم الحالي.
    */
    public function getMyCollectionStatistics(): array
    {
        $baseQuery = $this->getMyCollectionsQuery();

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'total_amount' => (clone $baseQuery)->where('status', 'approved')->sum('collected_amount'),
            'today_collections' => (clone $baseQuery)
                ->whereDate('collection_date', today())
                ->count(),
            'by_collection_type' => $this->getMyCollectionsByType(),
            'by_payment_type' => $this->getMyCollectionsByPaymentType(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Create My Collection
    |--------------------------------------------------------------------------
    | إضافة تحصيل شخصي جديد للمستخدم الحالي.
    */
    public function createMyCollection(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = 'pending';
            $data['added_by'] = auth('tenant')->id();

            // معالجة الملف المرفق
            if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                $data['attachment'] = $this->handleFileUpload($data['attachment']);
            }

            return Collection::create($data);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update My Collection
    |--------------------------------------------------------------------------
    | تحديث تحصيلي الشخصي مع التحقق من الصلاحيات.
    */
    public function updateMyCollection(Collection $collection, array $data, int $userId): Collection
    {
        return DB::transaction(function () use ($collection, $data, $userId) {
            if (!$this->canUserEditCollection($collection, $userId)) {
                throw new \Exception('غير مسموح لك بتعديل هذا التحصيل');
            }
            if ($collection->status !== 'pending') {
                throw new \Exception('لا يمكن تعديل التحصيل بعد المراجعة');
            }

            // معالجة الملف المرفق
            if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                // حذف الملف القديم إذا وجد
                if ($collection->attachment) {
                    $this->deleteFile($collection->attachment);
                }
                $data['attachment'] = $this->handleFileUpload($data['attachment']);
            } elseif (isset($data['remove_attachment']) && $data['remove_attachment']) {
                if ($collection->attachment) {
                    $this->deleteFile($collection->attachment);
                }
                $data['attachment'] = null;
            }

            $collection->update($data);
            return $collection->fresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Get My Recent Collections
    |--------------------------------------------------------------------------
    | جلب تحصيلاتي الحديثة خلال فترة محددة.
    */
    public function getMyRecentCollections(int $days = 7): Builder
    {
        return $this->getMyCollectionsQuery()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Collections by Type
    |--------------------------------------------------------------------------
    | جلب تحصيلاتي مجمعة حسب النوع (نقدي/مجدول).
    */
    private function getMyCollectionsByType(): array
    {
        return Collection::selectRaw('collection_type, COUNT(*) as count, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->where('added_by', auth('tenant')->id())
            ->groupBy('collection_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->collection_type === 'cash' ? 'نقدي' : 'مجدول',
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Collections by Payment Type
    |--------------------------------------------------------------------------
    | جلب تحصيلاتي مجمعة حسب نوع السداد (بتسوية/بدون تسوية).
    */
    private function getMyCollectionsByPaymentType(): array
    {
        return Collection::selectRaw('payment_type, COUNT(*) as count, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->where('added_by', auth('tenant')->id())
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->payment_type === 'settlement' ? 'سداد بتسوية' : 'سداد بدون تسوية',
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | للمدير - جميع التحصيلات
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Get All Collections Query
    |--------------------------------------------------------------------------
    | جلب استعلام جميع التحصيلات (للمدراء) بدون فلترة حسب المستخدم.
    */
    public function getAllCollectionsQuery(): Builder
    {
        return $this->getCollectionsQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Collection Statistics
    |--------------------------------------------------------------------------
    | جلب إحصائيات جميع التحصيلات (للمدراء).
    */
    public function getAllCollectionStatistics(): array
    {
        $baseQuery = $this->getAllCollectionsQuery();

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'total_amount' => (clone $baseQuery)->where('status', 'approved')->sum('collected_amount'),
            'today_collections' => (clone $baseQuery)
                ->whereDate('collection_date', today())
                ->count(),
            'by_collection_type' => $this->getCollectionsByType(),
            'by_payment_type' => $this->getCollectionsByPaymentType(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Selected Collections
    |--------------------------------------------------------------------------
    | اعتماد تحصيلات متعددة (للمدراء).
    */
    public function approveSelectedCollections(array $collectionIds, int $approvedBy): array
    {
        $approvedCollections = [];
        $failedCollections = [];

        $collectionsToProcess = Collection::whereIn('id', $collectionIds)
            ->where('status', 'pending')
            ->with('customer')
            ->get();

        if ($collectionsToProcess->isEmpty()) {
            throw new \Exception('لم يتم العثور على تحصيلات صالحة للاعتماد.');
        }

        foreach ($collectionsToProcess as $collection) {
            $remainingDebt = $collection->customer ? $collection->customer->remaining_debt : 0;

            if ($collection->collected_amount > $remainingDebt) {
                $failedCollections[] = $collection->receipt_number ?? "ID:{$collection->id}";
            } else {
                DB::transaction(function () use ($collection, $approvedBy) {
                    $collection->update([
                        'status' => 'approved',
                        'approved_by' => $approvedBy,
                        'approved_at' => now(),
                        'rejection_reason' => null,
                    ]);

                    $this->createCreditTransaction($collection, $approvedBy);

                    $customerFollow = CustomerFollow::where('collection_id', $collection->id)->first();

                    if ($customerFollow) {
                        $customer = $collection->customer;
                        $latestFollow = CustomerFollow::where('customer_id', $customer->id)
                            ->latest('created_at')
                            ->first();

                        $isLatestFollow = ($latestFollow && $latestFollow->id === $customerFollow->id);

                        if ($isLatestFollow) {
                            $customer->update([
                                'last_contact_result_id' => $customerFollow->contact_result_id,
                                'customer_status_id' => $customerFollow->customer_status_id,
                            ]);
                        }
                    }

                    $this->notificationService->sendCollectionApprovedNotification($collection, $collection->customer);
                });

                $approvedCollections[] = $collection->receipt_number ?? "ID:{$collection->id}";
            }
        }

        $message = "";
        if (!empty($approvedCollections)) {
            $message .= "تم اعتماد " . count($approvedCollections) . " تحصيل بنجاح. ";
        }
        if (!empty($failedCollections)) {
            $message .= "فشل اعتماد " . count($failedCollections) . " تحصيل بسبب تجاوز المديونية.";
        }

        return [
            'success' => true,
            'status' => !empty($failedCollections) ? 'partial' : 'full',
            'message' => trim($message),
            'details' => [
                'approved_count' => count($approvedCollections),
                'failed_count' => count($failedCollections),
                'failed_receipts' => $failedCollections
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Selected Collections
    |--------------------------------------------------------------------------
    | رفض تحصيلات متعددة (للمدراء).
    */
    public function rejectSelectedCollections(array $collectionIds, string $reason, int $rejectedBy): int
    {
        return DB::transaction(function () use ($collectionIds, $reason, $rejectedBy) {
            $collections = Collection::whereIn('id', $collectionIds)
                ->where('status', 'pending')
                ->get();

            if ($collections->isEmpty()) {
                throw new \Exception('لا توجد تحصيلات في انتظار المراجعة');
            }

            $rejectedCount = 0;

            foreach ($collections as $collection) {
                $collection->update([
                    'status' => 'rejected',
                    'approved_by' => $rejectedBy,
                    'approved_at' => now(),
                    'rejection_reason' => $reason,
                ]);
                // Notify customer about rejected collection -----------------------------------
                $this->notificationService->sendCollectionRejectedNotification($collection, $collection->customer, $reason);
                // ------------------------------------------------------------------------------
                $rejectedCount++;
            }

            return $rejectedCount;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Quick Stats for Admin
    |--------------------------------------------------------------------------
    | جلب إحصائيات سريعة للمدير للوحة التحكم.
    */
    public function getQuickStatsForAdmin(): array
    {
        return [
            'pending_today' => Collection::where('status', 'pending')
                ->whereDate('created_at', today())
                ->count(),
            'approved_today' => Collection::where('status', 'approved')
                ->whereDate('approved_at', today())
                ->count(),
            'total_amount_today' => Collection::where('status', 'approved')
                ->whereDate('collection_date', today())
                ->sum('collected_amount'),
            'active_users_today' => Collection::whereDate('created_at', today())
                ->distinct('added_by')
                ->count('added_by'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Selected Adimn Collections
    |--------------------------------------------------------------------------
    | حذف تحصيلات متعددة للادمن الحالي .
    */
    public function deleteSelectedAdminCollections(array $collectionIds): int
    {
        return DB::transaction(function () use ($collectionIds) {
            $collections = Collection::whereIn('id', $collectionIds)
                ->get();

            if ($collections->isEmpty()) {
                throw new \Exception('لم يتم العثور على أي تحصيلات تخصك');
            }

            // التحقق من وجود تحصيلات معتمدة أو مرفوضة
            $processedCollections = $collections->whereNotIn('status', ['pending']);
            if ($processedCollections->isNotEmpty()) {
                $processedCount = $processedCollections->count();
                throw new \Exception("لا يمكن حذف {$processedCount} تحصيل تمت مراجعته بالفعل");
            }

            $deletedCount = 0;

            foreach ($collections as $collection) {
                if ($collection->attachment) {
                    $this->deleteFile($collection->attachment);
                }

                if ($collection->delete()) {
                    $deletedCount++;
                }
            }

            if ($deletedCount === 0) {
                throw new \Exception('فشل في حذف التحصيلات المحددة');
            }

            return $deletedCount;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | الدوال المشتركة
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Check User Can Edit Collection
    |--------------------------------------------------------------------------
    | التحقق من إمكانية المستخدم تعديل التحصيل.
    */
    public function canUserEditCollection(Collection $collection, int $userId): bool
    {
        return $collection->added_by === $userId && $collection->status === 'pending';
    }

    /*
    |--------------------------------------------------------------------------
    | Check User Can View Collection
    |--------------------------------------------------------------------------
    | التحقق من إمكانية المستخدم عرض التحصيل.
    */
    public function canUserViewCollection(Collection $collection, int $userId): bool
    {
        // يمكن للمستخدم عرض تحصيلاته، أو للمدير عرض أي تحصيل
        return $collection->added_by === $userId || auth('tenant')->user()?->can('admin_collections.view');
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Collection
    |--------------------------------------------------------------------------
    | اعتماد التحصيل مع التحقق من الحالة.
    */
    public function approveCollection(Collection $collection, int $approvedBy): Collection
    {
        return DB::transaction(function () use ($collection, $approvedBy) {
            if ($collection->status === 'approved') {
                throw new \Exception('التحصيل معتمد بالفعل');
            }

            if ($collection->status !== 'pending') {
                throw new \Exception('لا يمكن اعتماد التحصيل في هذه الحالة');
            }

            $customer = Customer::find($collection->customer_id);
            if (!$customer) {
                throw new \Exception('العميل المرتبط بهذا التحصيل غير موجود');
            }

            $remainingDebt = $customer->remaining_debt;

            if ($collection->collected_amount > $remainingDebt) {
                $formattedAmount = number_format($collection->collected_amount, 2);
                $formattedDebt = number_format($remainingDebt, 2);
                throw new \Exception(
                    "لا يمكن اعتماد التحصيل. قيمة السند ({$formattedAmount}) أكبر من المديونية المتبقية ({$formattedDebt})."
                );
            }

            $collection->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            CompanyLetter::where('collection_id', $collection->id)
                ->where('status', 'pending_approval')
                ->update([
                    'status'      => 'approved_ready_to_send',
                    'approved_by' => $approvedBy,
                    'approved_at' => now()
                ]);

            $this->createCreditTransaction($collection, $approvedBy);

            $this->notificationService->sendCollectionApprovedNotification($collection, $customer);

            $customerFollow = CustomerFollow::where('collection_id', $collection->id)->first();

            if ($customerFollow) {
                $latestFollow = CustomerFollow::where('customer_id', $customer->id)
                    ->latest('created_at')
                    ->first();

                $isLatestFollow = ($latestFollow && $latestFollow->id === $customerFollow->id);

                if ($isLatestFollow) {
                    $customer->update([
                        'last_contact_result_id' => $customerFollow->contact_result_id,
                        'customer_status_id' => $customerFollow->customer_status_id,
                    ]);
                }
            }

            $newRemainingDebt = $remainingDebt - $collection->collected_amount;

            if ($newRemainingDebt <= 0) {
                $customer->update([
                    'last_contact_result_id' => 1
                ]);

                $lastFollow = CustomerFollow::where('customer_id', $customer->id)
                    ->latest('created_at')
                    ->first();

                if (!$lastFollow || $lastFollow->contact_result_id != 1) {
                    $autoFollow = CustomerFollow::create([
                        'customer_id' => $customer->id,
                        'contact_method_id' => 1,
                        'contact_result_id' => 1,
                        'customer_status_id' => 1,
                        'notes' => '[النظام] تم إنشاء المتابعة تلقائيًا (تم السداد بالكامل) بعد اعتماد سند رقم: ' . $collection->receipt_number,
                        'created_by' => $approvedBy,
                    ]);

                    FullPaymentDetails::create([
                        'contact_result_id' => $autoFollow->id,
                        'payment_amount' => $collection->collected_amount,
                        'payment_date' => $collection->collection_date,
                        'payment_type' => $collection->payment_type,
                        'collection_type' => $collection->collection_type,
                        'payment_method_id' => $collection->payment_method_id,
                        'payment_receipt_path' => $collection->attachment,
                    ]);
                }
            }

            return $collection->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Collection
    |--------------------------------------------------------------------------
    | رفض التحصيل مع تسجيل سبب الرفض.
    */
    public function rejectCollection(Collection $collection, string $reason, int $rejectedBy): Collection
    {
        return DB::transaction(function () use ($collection, $reason, $rejectedBy) {
            if ($collection->status === 'rejected') {
                throw new \Exception('التحصيل مرفوض بالفعل');
            }

            if ($collection->status !== 'pending') {
                throw new \Exception('لا يمكن رفض التحصيل في هذه الحالة');
            }

            $collection->update([
                'status' => 'rejected',
                'approved_by' => $rejectedBy,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $customer = Customer::find($collection->customer_id);
            if ($customer) {
                $this->notificationService->sendCollectionRejectedNotification($collection, $customer, $reason);
            }

            return $collection->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collections Query
    |--------------------------------------------------------------------------
    | جلب استعلام التحصيلات الأساسي مع العلاقات المطلوبة.
    */
    public function getCollectionsQuery(): Builder
    {
        return Collection::select([
            'id',
            'customer_id',
            'collected_amount',
            'collection_type',
            'payment_method_id',
            'receipt_number',
            'closing_code',
            'collection_date',
            'payment_type',
            'attachment',
            'status',
            'added_by',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'created_at'
        ])->with([
            'customer:id,name,account_number',
            'paymentMethod:id,name',
            'addedBy:id,name',
            'approvedBy:id,name'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Collections Query
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر المختلفة على استعلام التحصيلات.
    */
    public function filterCollectionsQuery(Builder $query, array $filters): Builder
    {
        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by customer
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by payment method
        if (!empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        // Filter by collection type
        if (!empty($filters['collection_type'])) {
            $query->where('collection_type', $filters['collection_type']);
        }

        // Filter by payment type
        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        // Filter by added user (للمدراء)
        if (!empty($filters['added_by'])) {
            $query->where('added_by', $filters['added_by']);
        }

        // Filter by date range
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('collection_date', [$filters['date_from'], $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $query->whereDate('collection_date', '>=', $filters['date_from']);
        } elseif (!empty($filters['date_to'])) {
            $query->whereDate('collection_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Customers
    |--------------------------------------------------------------------------
    | جلب العملاء النشطين للقوائم المنسدلة.
    */
    public function getActiveCustomers()
    {
        $query = Customer::select('id', 'name', 'phone', 'account_number');

        if (auth('tenant')->user()?->can('customers.view')) {
            return $query->orderBy('account_number')->get();
        }

        if (auth('tenant')->user()?->can('customers.my_view')) {
            return $query->where('relationship_manager_id', auth('tenant')->id())
                ->orderBy('account_number')
                ->get();
        }

        return collect([]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Payment Methods
    |--------------------------------------------------------------------------
    | جلب طرق الدفع النشطة للقوائم المنسدلة.
    */
    public function getActivePaymentMethods()
    {
        return SettingsPaymentMethod::where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Users
    |--------------------------------------------------------------------------
    | جلب المستخدمين النشطين للقوائم المنسدلة (للمدراء).
    */
    public function getActiveUsers()
    {
        return User::select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Receipt Number
    |--------------------------------------------------------------------------
    | توليد رقم سند فريد للتحصيل الجديد.
    */
    public function generateReceiptNumber(): string
    {
        $prefix = 'RC';
        $date = now()->format('Ymd');
        $lastCollection = Collection::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCollection ?
            (int)substr($lastCollection->receipt_number, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collections by Type
    |--------------------------------------------------------------------------
    | جلب التحصيلات مجمعة حسب النوع (نقدي/مجدول).
    */
    public function getCollectionsByType(): array
    {
        return Collection::selectRaw('collection_type, COUNT(*) as count, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->groupBy('collection_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->collection_type === 'cash' ? 'نقدي' : 'مجدول',
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collections by Payment Type
    |--------------------------------------------------------------------------
    | جلب التحصيلات مجمعة حسب نوع السداد.
    */
    public function getCollectionsByPaymentType(): array
    {
        return Collection::selectRaw('payment_type, COUNT(*) as count, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->groupBy('payment_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->payment_type === 'settlement' ? 'سداد بتسوية' : 'سداد بدون تسوية',
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | دوال مساعدة خاصة بالخدمة
    |--------------------------------------------------------------------------
    */
    private function createCreditTransaction(Collection $collection, int $userId): void
    {
        if ($collection->financialTransactions()->exists()) {
            return;
        }

        CustomerTransaction::create([
            'customer_id'       => $collection->customer_id,
            'type'              => 'credit',
            'amount'            => $collection->collected_amount,
            'transaction_date'  => $collection->collection_date,
            'description'       => "سداد معتمد (سند رقم: {$collection->receipt_number})",
            'related_type'      => Collection::class,
            'related_id'        => $collection->id,
            'created_by'        => $userId,
        ]);
    }

    /*    
    |--------------------------------------------------------------------------
    | Get Collections by User
    |--------------------------------------------------------------------------
    | جلب التحصيلات مجمعة حسب المستخدم (للتقارير).
    */
    private function getCollectionsByUser(array $filters): array
    {
        $query = Collection::selectRaw('added_by, COUNT(*) as count, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->with('addedBy:id,name')
            ->groupBy('added_by');

        // Apply date filters
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('collection_date', [$filters['date_from'], $filters['date_to']]);
        }

        return $query->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->addedBy ? $item->addedBy->name : 'غير محدد',
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collections by Date
    |--------------------------------------------------------------------------
    | جلب التحصيلات مجمعة حسب التاريخ (للتقارير والرسوم البيانية).
    */
    private function getCollectionsByDate(array $filters): array
    {
        $query = Collection::selectRaw('DATE(collection_date) as date, COUNT(*) as count, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->groupBy('date')
            ->orderBy('date', 'desc');

        // Apply date filters
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('collection_date', [$filters['date_from'], $filters['date_to']]);
        } else {
            // Default to last 30 days
            $query->where('collection_date', '>=', now()->subDays(30));
        }

        return $query->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }


    /*
    |--------------------------------------------------------------------------
    | Handle File Upload
    |--------------------------------------------------------------------------
    | معالجة رفع الملفات المرفقة مع التحصيل.
    */
    private function handleFileUpload($file): ?string
    {
        if (!$file) {
            return null;
        }

        // التحقق من أن الملف صالح
        if (!$file instanceof UploadedFile) {
            return null;
        }

        // التحقق من نوع الملف
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('نوع الملف غير مدعوم');
        }

        // التحقق من حجم الملف (2 ميجابايت كحد أقصى)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \Exception('حجم الملف يتجاوز الحد الأقصى المسموح (2 ميجابايت)');
        }

        // إنشاء اسم فريد للملف
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // حفظ الملف
        $path = $file->storeAs('collections/attachments', $fileName, 'tenant_public');

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete File
    |--------------------------------------------------------------------------
    */
    private function deleteFile(?string $filePath): bool
    {
        if (!$filePath) {
            return true;
        }

        return Storage::disk('tenant_public')->delete($filePath);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Collection (Legacy)
    |--------------------------------------------------------------------------
    | إنشاء تحصيل (للتوافق مع الكود القديم).
    | @deprecated استخدم createMyCollection بدلاً منها
    */
    public function createCollection(array $data): Collection
    {
        return $this->createMyCollection($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Collection (Legacy)
    |--------------------------------------------------------------------------
    | تحديث تحصيل (للتوافق مع الكود القديم).
    | @deprecated استخدم updateMyCollection بدلاً منها
    */
    public function updateCollection(Collection $collection, array $data): Collection
    {
        return $this->updateMyCollection($collection, $data, auth('tenant')->id());
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Collection (Admin or Authorized)
    |--------------------------------------------------------------------------
    | حذف تحصيل بشكل كامل مع حذف الدفعات المرتبطة به.
    */
    public function deleteCollection(Collection $collection): bool
    {
        return DB::transaction(function () use ($collection) {

            if ($collection->attachment) {
                $this->deleteFile($collection->attachment);
            }

            CustomerTransaction::where('related_type', Collection::class)
                ->where('related_id', $collection->id)
                ->delete();

            return $collection->delete();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Selected Collections (Legacy)
    |--------------------------------------------------------------------------
    | حذف تحصيلات محددة (للتوافق مع الكود القديم).
    | @deprecated استخدم deleteSelectedMyCollections بدلاً منها
    */
    public function deleteSelectedCollections(array $collectionIds): int
    {
        return DB::transaction(function () use ($collectionIds) {
            $collections = Collection::whereIn('id', $collectionIds)->get();

            if ($collections->isEmpty()) {
                throw new \Exception('لم يتم العثور على أي تحصيلات محددة');
            }

            $deletedCount = 0;

            foreach ($collections as $collection) {
                // حذف المرفق إن وجد
                if ($collection->attachment) {
                    $this->deleteFile($collection->attachment);
                }

                // حذف الدفعات المرتبطة
                CustomerTransaction::where('related_type', Collection::class)
                    ->where('related_id', $collection->id)
                    ->delete();

                // حذف التحصيل
                if ($collection->delete()) {
                    $deletedCount++;
                }
            }

            if ($deletedCount === 0) {
                throw new \Exception('فشل في حذف التحصيلات المحددة');
            }

            return $deletedCount;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collection Statistics (Legacy)
    |--------------------------------------------------------------------------
    | إحصائيات التحصيلات (للتوافق مع الكود القديم).
    | @deprecated استخدم getMyCollectionStatistics بدلاً منها
    */
    public function getCollectionStatistics(): array
    {
        return $this->getMyCollectionStatistics();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Recent Collections (Legacy)
    |--------------------------------------------------------------------------
    | التحصيلات الحديثة (للتوافق مع الكود القديم).
    | @deprecated استخدم getMyRecentCollections بدلاً منها
    */
    public function getRecentCollections(int $days = 7): Builder
    {
        return $this->getMyRecentCollections($days);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collections by Status
    |--------------------------------------------------------------------------
    | جلب التحصيلات حسب الحالة (للمستخدم الحالي).
    */
    public function getCollectionsByStatus(string $status): Builder
    {
        return $this->getMyCollectionsQuery()
            ->where('status', $status)
            ->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    |  البحث في العملاء بـ AJAX
    */
    public function searchCustomers(?string $search = '', int $limit = 20): array
    {
        $query = Customer::select('id', 'name', 'account_number', 'phone');

        if (auth('tenant')->user()?->can('customers.view')) {
        } elseif (auth('tenant')->user()?->can('customers.my_view')) {
            $query->where('relationship_manager_id', auth('tenant')->id());
        } else {
            return [];
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('account_number')
            ->limit($limit)
            ->get()
            ->unique('account_number')
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->account_number
                ];
            })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer ById
    |--------------------------------------------------------------------------
    | جلب عميل محدد
    */
    public function getCustomerById(int $customerId): ?array
    {
        $customer = Customer::select('id', 'name', 'account_number')
            ->find($customerId);

        if (!$customer) {
            return null;
        }
        if (
            !auth('tenant')->user()?->can('customers.view') &&
            auth('tenant')->user()?->can('customers.my_view') &&
            $customer->relationship_manager_id !== auth('tenant')->id()
        ) {
            return null;
        }

        return [
            'id' => $customer->id,
            'text' => $customer->account_number
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Initial Customers
    |--------------------------------------------------------------------------
    | جلب العملاء الأولية (10 عملاء)
    */
    public function getInitialCustomers(int $limit = 10): array
    {
        $query = Customer::select('id', 'name', 'account_number', 'phone');

        if (!auth('tenant')->user()?->can('customers.view') && auth('tenant')->user()?->can('customers.my_view')) {
            $query->where('relationship_manager_id', auth('tenant')->id());
        } elseif (!auth('tenant')->user()?->can('customers.view')) {
            return [];
        }

        return $query->orderBy('account_number')
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => $customer->account_number
                ];
            })->toArray();
    }
}

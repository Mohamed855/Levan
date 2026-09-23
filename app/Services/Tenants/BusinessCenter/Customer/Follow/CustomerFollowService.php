<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Follow;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Follow\FullPaymentDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\PartialPaymentDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\PromisePaymentDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CallbackDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\DocumentRequestDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\ObjectionDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\RecoveryDetails;
use App\Models\Tenants\BusinessCenter\Customers\Follow\TemporaryContactLossDetails;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Customers\PaymentPlan\CustomerPaymentPlan;
use App\Models\Tenants\FinancialCollection\Collection\Collection as CollectionCollection;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason;
use App\Services\Tenants\BusinessCenter\Customer\PaymentPlan\CustomerPaymentPlanService;
use App\Services\Tenants\FinancialCollection\Collection\CollectionService;
use App\Services\Tenants\Notification\NotificationService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CustomerFollowService
{
    protected ContactResultService $contactResultService;
    protected CollectionService $collectionService;
    protected NotificationService $notificationService;
    protected CustomerPaymentPlanService $customerPaymentPlanService;

    public function __construct(
        ContactResultService $contactResultService,
        CollectionService $collectionService,
        NotificationService $notificationService,
        CustomerPaymentPlanService $customerPaymentPlanService
    ) {
        $this->contactResultService = $contactResultService;
        $this->collectionService = $collectionService;
        $this->notificationService = $notificationService;
        $this->customerPaymentPlanService = $customerPaymentPlanService;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Customer Follow
    |--------------------------------------------------------------------------
    */
    public function createCustomerFollow(array $data): CustomerFollow
    {

        $this->ensureNoPendingCollection($data['customer_id'] ?? null);

        return DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::id();
            $data = $this->handleUploadedFiles($data);

            $customerFollow = CustomerFollow::create($data);

            $this->completePendingTasksForCustomer($customerFollow->customer_id);

            $this->saveContactResultDetails($customerFollow, $data);
            $this->updateCustomerStatus($customerFollow, $data);

            $this->createTaskFromFollow($customerFollow, $data);

            if (in_array($data['contact_result_id'], [1, 2])) {
                $collection = $this->createCollectionFromFollow($customerFollow, $data);

                if ($collection) {
                    $this->notificationService->sendCollectionCreatedNotification($collection, $customerFollow->customer);
                }
            }
            if ($data['contact_result_id'] == 3) {
                $this->createPaymentPlanFromPromise($customerFollow, $data);
            }

            if ($data['contact_result_id'] == 2 && !empty($data['next_partial_payment_date'])) {
                $this->createPaymentPlanFromPartialPayment($customerFollow, $data);

                Task::create([
                    'customer_id' => $customerFollow->customer_id,
                    'follow_up_id' => $customerFollow->id,
                    'task_type' => 'promise',
                    'due_date' => $data['next_partial_payment_date'],
                    'status' => 'pending',
                ]);
            }

            $this->createCompanyLetterIfNeeded($customerFollow, $data);
            if (isset($data['selected_account_ids'])) {
                $selectedAccountIds = is_string($data['selected_account_ids'])
                    ? json_decode($data['selected_account_ids'], true)
                    : $data['selected_account_ids'];

                if (is_array($selectedAccountIds) && count($selectedAccountIds) > 0) {
                    $this->applyFollowToSelectedAccounts($customerFollow, $data, $selectedAccountIds);
                }
            }

            return $customerFollow;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Follow to Selected Accounts
    |--------------------------------------------------------------------------
    */
    private function applyFollowToSelectedAccounts(CustomerFollow $originalFollow, array $originalData, array $selectedAccountIds): void
    {
        $currentCustomerId = $originalFollow->customer_id;

        if (empty($selectedAccountIds)) {
            return;
        }

        try {
            $currentCustomer = $originalFollow->customer;

            if (!$currentCustomer || !$currentCustomer->identity_number) {
                return;
            }

            // ✅ جلب جميع الحسابات المحددة مع الشروط
            $selectedAccounts = Customer::whereIn('id', $selectedAccountIds)
                ->where('identity_number', $currentCustomer->identity_number)
                ->where('company_id', $currentCustomer->company_id)
                ->where('relationship_manager_id', $currentCustomer->relationship_manager_id)
                ->where(function ($query) {
                    $query->whereNull('last_contact_result_id')
                        ->orWhere('last_contact_result_id', '!=', 1);
                })
                ->get();

            // Log::info('Selected accounts for follow application', [
            //     'requested_ids' => $selectedAccountIds,
            //     'found_accounts' => $selectedAccounts->pluck('id')->toArray(),
            //     'filtered_count' => $selectedAccounts->count()
            // ]);

            if ($selectedAccounts->isEmpty()) {
                // Log::info('No eligible accounts found for follow application', [
                //     'requested_ids' => $selectedAccountIds
                // ]);
                return;
            }

            $successCount = 0;
            $skippedCount = 0;

            foreach ($selectedAccounts as $account) {
                // ✅ تخطي الحساب الحالي (تم إضافة المتابعة له بالفعل)
                if ($account->id == $currentCustomerId) {
                    $skippedCount++;
                    // Log::debug('Skipped current customer account', ['account_id' => $account->id]);
                    continue;
                }

                // ✅ التحقق من عدم وجود متابعة مماثلة اليوم
                $hasRecentFollow = CustomerFollow::where('customer_id', $account->id)
                    ->where('contact_result_id', $originalData['contact_result_id'])
                    ->where('created_by', Auth::id())
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if ($hasRecentFollow) {
                    $skippedCount++;
                    // Log::debug('Skipped account with recent follow', [
                    //     'account_id' => $account->id,
                    //     'contact_result_id' => $originalData['contact_result_id']
                    // ]);
                    continue;
                }

                // إكمال المهام المعلقة للحساب المرتبط قبل إضافة المتابعة الجديدة
                $this->completePendingTasksForCustomer($account->id);

                // ✅ إنشاء المتابعة للحساب المرتبط
                $this->createFollowForRelatedCustomer($account, $originalData);
                $successCount++;
            }

            // Log::info('Follow application completed', [
            //     'original_follow_id' => $originalFollow->id,
            //     'total_requested' => count($selectedAccountIds),
            //     'eligible_accounts' => $selectedAccounts->count(),
            //     'successful' => $successCount,
            //     'skipped' => $skippedCount
            // ]);
        } catch (\Exception $e) {
            // Log::error('Error applying follow to selected accounts: ' . $e->getMessage(), [
            //     'original_follow_id' => $originalFollow->id,
            //     'selected_account_ids' => $selectedAccountIds,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Uploaded Files
    |--------------------------------------------------------------------------
    */
    private function handleUploadedFiles(array $data, ?CustomerFollow $customerFollow = null): array
    {
        if (isset($data['payment_receipt']) && $data['payment_receipt'] instanceof UploadedFile) {
            $path = $data['payment_receipt']->store('payment_receipts', 'tenant_public');
            $data['payment_receipt_path'] = $path;
        }

        if (isset($data['partial_payment_receipt']) && $data['partial_payment_receipt'] instanceof UploadedFile) {
            $path = $data['partial_payment_receipt']->store('payment_receipts', 'tenant_public');
            $data['partial_payment_receipt_path'] = $path;
        }

        if (isset($data['recovery_document']) && $data['recovery_document'] instanceof UploadedFile) {
            $path = $data['recovery_document']->store('recovery_documents', 'tenant_public');
            $data['recovery_document_path'] = $path;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer Follow
    |--------------------------------------------------------------------------
    */
    public function updateCustomerFollow(CustomerFollow $customerFollow, array $data): CustomerFollow
    {
        return DB::transaction(function () use ($customerFollow, $data) {

            $oldContactResultId = $customerFollow->contact_result_id;
            $latestFollow = CustomerFollow::where('customer_id', $customerFollow->customer_id)
                ->latest('created_at')
                ->first();
            $isLatestFollow = ($latestFollow && $latestFollow->id === $customerFollow->id);

            $data = $this->handleUploadedFiles($data, $customerFollow);

            $customerFollow->update($data);

            $this->deleteContactResultDetails($customerFollow);
            $this->saveContactResultDetails($customerFollow, $data);

            if ($isLatestFollow) {
                $this->updateCustomerStatus($customerFollow, $data);
            }

            // تحديث المهام المرتبطة بهذه المتابعة
            $this->updateTaskFromFollow($customerFollow, $data, $oldContactResultId);

            if (in_array($data['contact_result_id'], [1, 2])) {
                $this->createOrUpdateCollectionFromFollow($customerFollow, $data);
            } else {
                $this->deleteRelatedCollection($customerFollow);
            }

            $this->handlePaymentPlanOnUpdate($customerFollow, $data, $oldContactResultId);

            return $customerFollow->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Create Collection From Follow
    |--------------------------------------------------------------------------
    */
    private function createCollectionFromFollow(CustomerFollow $customerFollow, array $data): ?CollectionCollection
    {
        $collectionData = $this->prepareCollectionData($customerFollow, $data);

        if ($customerFollow->fullPaymentDetails?->payment_receipt_path) {
            $collectionData['attachment'] = $customerFollow->fullPaymentDetails->payment_receipt_path;
        } elseif ($customerFollow->partialPaymentDetails?->partial_payment_receipt_path) {
            $collectionData['attachment'] = $customerFollow->partialPaymentDetails->partial_payment_receipt_path;
        }

        $collection = $this->collectionService->createMyCollection($collectionData);

        if (!$collection) {
            return null;
        }

        $customerFollow->update([
            'collection_id' => $collection->id
        ]);

        return $collection;
    }

    /*
    |--------------------------------------------------------------------------
    | Copy File to Collections Directory
    |--------------------------------------------------------------------------
    */
    private function copyFileToCollections(string $sourcePath, string $destinationDirectory): ?string
    {
        if (!$sourcePath) {
            return null;
        }

        $sourceFullPath = storage_path('app/public/' . $sourcePath);
        if (!file_exists($sourceFullPath)) {
            return null;
        }

        $fileName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $newFileName = 'collection_' . time() . '_' . uniqid() . '.' . $extension;
        $destinationPath = $destinationDirectory . '/' . $newFileName;

        Storage::disk('tenant_public')->copy($sourcePath, $destinationPath);

        return $destinationPath;
    }

    /*
    |--------------------------------------------------------------------------
    | Create or Update Collection From Follow
    |--------------------------------------------------------------------------
    */
    private function createOrUpdateCollectionFromFollow(CustomerFollow $customerFollow, array $data): void
    {
        $collectionData = $this->prepareCollectionData($customerFollow, $data);

        if (isset($data['payment_receipt_path'])) {
            $collectionData['attachment'] = $this->copyFileToCollections(
                $data['payment_receipt_path'],
                'collections/attachments'
            );
        } elseif (isset($data['partial_payment_receipt_path'])) {
            $collectionData['attachment'] = $this->copyFileToCollections(
                $data['partial_payment_receipt_path'],
                'collections/attachments'
            );
        }

        if ($customerFollow->collection_id) {
            $collection = $this->collectionService->updateMyCollection(
                $customerFollow->collection,
                $collectionData,
                Auth::id()
            );
        } else {
            $collection = $this->collectionService->createMyCollection($collectionData);
            $customerFollow->update(['collection_id' => $collection->id]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Collection Data
    |--------------------------------------------------------------------------
    */
    private function prepareCollectionData(CustomerFollow $customerFollow, array $data): array
    {
        $paymentData = $data['contact_result_id'] == 1
            ? [
                'amount' => $data['payment_amount'],
                'date' => $data['payment_date'],
                'type' => $data['payment_type'],
                'receipt_path' => $data['payment_receipt_path'] ?? null,
            ]
            : [
                'amount' => $data['partial_payment_amount'],
                'date' => $data['partial_payment_date'],
                'type' => $data['partial_payment_type'],
                'receipt_path' => $data['partial_payment_receipt_path'] ?? null,
            ];

        return [
            'customer_id' => $customerFollow->customer_id,
            'collected_amount' => $paymentData['amount'],
            'collection_type' => 'cash',
            'payment_method_id' => $data['payment_method_id'],
            'receipt_number' => $this->collectionService->generateReceiptNumber(),
            'collection_date' => $paymentData['date'],
            'payment_type' => in_array($paymentData['type'], ['settlement'])
                ? 'settlement'
                : 'without_settlement',
            'attachment' => $paymentData['receipt_path'],
            'status' => 'pending',
            'added_by' => Auth::id(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Related Collection
    |--------------------------------------------------------------------------
    */
    private function deleteRelatedCollection(CustomerFollow $customerFollow): void
    {
        if ($customerFollow->collection_id && $customerFollow->collection) {
            if ($customerFollow->collection->attachment) {
                Storage::disk('tenant_public')->delete($customerFollow->collection->attachment);
            }
            $customerFollow->collection->delete();
            $customerFollow->update(['collection_id' => null]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer Status
    |--------------------------------------------------------------------------
    */
    private function updateCustomerStatus(CustomerFollow $customerFollow, array $data): void
    {
        $customer = $customerFollow->customer;
        if (!$customer) {
            return;
        }

        $contactResultId = $data['contact_result_id'];
        $needsApproval = in_array($contactResultId, [1, 2]);
        $updateData = [
            'customer_status_id' => $data['customer_status_id'] ?? $customer->customer_status_id,
        ];
        if (!$needsApproval) {
            $updateData['last_contact_result_id'] = $contactResultId;
        }
        $customer->update($updateData);
    }

    /*
    |--------------------------------------------------------------------------
    | Save Contact Result Details
    |--------------------------------------------------------------------------
    */
    private function saveContactResultDetails(CustomerFollow $customerFollow, array $data): void
    {
        $contactResultId = $data['contact_result_id'];

        switch ($contactResultId) {
            case 1: // تم السداد
                FullPaymentDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'payment_amount' => $data['payment_amount'],
                    'payment_date' => $data['payment_date'],
                    'payment_type' => $data['payment_type'],
                    'collection_type' => $data['collection_type'] ?? 'cash',
                    'payment_method_id' => $data['payment_method_id'],
                    'payment_receipt_path' => $data['payment_receipt_path'] ?? null,
                ]);
                break;

            case 2: // سداد جزئي
                PartialPaymentDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'partial_payment_amount' => $data['partial_payment_amount'],
                    'partial_payment_date' => $data['partial_payment_date'],
                    'partial_payment_type' => $data['partial_payment_type'],
                    'collection_type' => $data['collection_type'],
                    'payment_method_id' => $data['payment_method_id'],
                    'partial_payment_receipt_path' => $data['partial_payment_receipt_path'] ?? null,
                    'next_partial_payment_date' => $data['next_partial_payment_date'] ?? null,
                ]);
                break;


            case 3: // وعد بالسداد
                PromisePaymentDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'promise_amount' => $data['promise_amount'],
                    'promise_date' => $data['promise_date'],
                ]);
                break;

            case 4: // معاودة الاتصال
                CallbackDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'callback_date' => $data['callback_date'],
                    'callback_reason' => $data['callback_reason'],
                    'callback_reason_other' => $data['callback_reason_other'] ?? null,
                ]);
                break;

            case 9: // طلب مستندات
                DocumentRequestDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'documents_date' => $data['documents_date'],
                    'documents_reason_id' => $data['documents_reason'],
                ]);
                break;

            case 10: // معترض
                ObjectionDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'objection_reason' => $data['objection_reason'],
                ]);
                break;

            case 11: // حذف مسترد
                RecoveryDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'recovery_reason' => $data['recovery_reason'],
                    'recovery_reason_other' => $data['recovery_reason_other'] ?? null,
                    'recovery_document_path' => $data['recovery_document_path'] ?? null,
                ]);
                break;

            case 12: // عدم التواصل مؤقتاً
                TemporaryContactLossDetails::create([
                    'contact_result_id' => $customerFollow->id,
                    'temporary_contact_reason' => $data['temporary_contact_reason'],
                    'temporary_contact_reason_other' => $data['temporary_contact_reason_other'] ?? null,
                ]);
                break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Contact Result Details
    |--------------------------------------------------------------------------
    */
    private function deleteContactResultDetails(CustomerFollow $customerFollow): void
    {
        $customerFollow->fullPaymentDetails()->delete();
        $customerFollow->partialPaymentDetails()->delete();
        $customerFollow->promisePaymentDetails()->delete();
        $customerFollow->callbackDetails()->delete();
        $customerFollow->documentRequestDetails()->delete();
        $customerFollow->objectionDetails()->delete();
        $customerFollow->recoveryDetails()->delete();
        $customerFollow->temporaryContactLossDetails()->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Customer Follow
    |--------------------------------------------------------------------------
    */
    public function deleteCustomerFollow(CustomerFollow $customerFollow): bool
    {
        return DB::transaction(function () use ($customerFollow) {
            $this->deleteRelatedCollection($customerFollow);
            $this->deleteContactResultDetails($customerFollow);
            $this->deleteRelatedPaymentPlan($customerFollow);
            return $customerFollow->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Follows Query
    |--------------------------------------------------------------------------
    */
    public function getCustomerFollowsQuery(int $customerId): Builder
    {
        return CustomerFollow::where('customer_id', $customerId)
            ->with([
                'customer:id,name',
                'contactMethod:id,name',
                'customerStatus:id,name',
                'contactResult:id,name',
                'createdBy:id,name',
                'collection',
                'fullPaymentDetails',
                'partialPaymentDetails',
                'promisePaymentDetails',
                'callbackDetails',
                'documentRequestDetails',
                'objectionDetails',
                'recoveryDetails',
                'temporaryContactLossDetails'
            ])
            ->select([
                'id',
                'customer_id',
                'contact_method_id',
                'contact_location',
                'contacted_person',
                'contacted_person_name',
                'next_contact_datetime',
                'customer_status_id',
                'contact_result_id',
                'notes',
                'created_by',
                'collection_id',
                'created_at'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Customer Follows Query
    |--------------------------------------------------------------------------
    */
    public function filterCustomerFollowsQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['contact_method_id'])) {
            $query->where('contact_method_id', $filters['contact_method_id']);
        }
        if (!empty($filters['customer_status_id'])) {
            $query->where('customer_status_id', $filters['customer_status_id']);
        }
        if (!empty($filters['contact_result_id'])) {
            $query->where('contact_result_id', $filters['contact_result_id']);
        }
        if (!empty($filters['contacted_person'])) {
            $query->where('contacted_person', $filters['contacted_person']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contact_location', 'like', "%{$searchTerm}%")
                    ->orWhere('contacted_person_name', 'like', "%{$searchTerm}%")
                    ->orWhere('notes', 'like', "%{$searchTerm}%");
            });
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Follow Statistics
    |--------------------------------------------------------------------------
    */
    public function getCustomerFollowStatistics(int $customerId): array
    {
        $baseQuery = CustomerFollow::where('customer_id', $customerId);
        return [
            'total' => (clone $baseQuery)->count(),
            'recent' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(),
            'by_contact_result' => (clone $baseQuery)
                ->join('contact_results', 'customer_follows.contact_result_id', '=', 'contact_results.id')
                ->select('contact_results.name', DB::raw('count(*) as count'))
                ->groupBy('contact_results.name')
                ->pluck('count', 'contact_results.name')
                ->toArray(),
            'upcoming_contacts' => (clone $baseQuery)
                ->whereNotNull('next_contact_datetime')
                ->where('next_contact_datetime', '>', now())
                ->count(),
            'with_collections' => (clone $baseQuery)
                ->whereNotNull('collection_id')
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        try {
            $contactMethods = SettingsContactMethod::where('status', 'active')->select('id', 'name')->orderBy('name')->get();
            $customerStatuses = SettingsCustomerStatus::where('status', 'active')->select('id', 'name')->orderBy('name')->get();
            $contactResults = $this->contactResultService->getAllContactResults();

            return [
                'contact_methods' => $contactMethods,
                'customer_statuses' => $customerStatuses,
                'contact_results' => $contactResults,
            ];
        } catch (\Exception $e) {
            return [
                'contact_methods' => collect([]),
                'customer_statuses' => collect([]),
                'contact_results' => collect([]),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Recent Customer Follows
    |--------------------------------------------------------------------------
    */
    public function getRecentCustomerFollows(int $customerId, int $limit = 10): Collection
    {
        return CustomerFollow::where('customer_id', $customerId)
            ->with([
                'contactMethod:id,name',
                'customerStatus:id,name',
                'contactResult:id,name',
                'createdBy:id,name',
                'collection',
                'fullPaymentDetails',
                'partialPaymentDetails',
                'promisePaymentDetails',
                'callbackDetails',
                'documentRequestDetails',
                'objectionDetails',
                'recoveryDetails'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Upcoming Contacts
    |--------------------------------------------------------------------------
    */
    public function getUpcomingContacts(int $customerId): Collection
    {
        return CustomerFollow::where('customer_id', $customerId)
            ->whereNotNull('next_contact_datetime')
            ->where('next_contact_datetime', '>', now())
            ->with([
                'contactMethod:id,name',
                'customerStatus:id,name',
                'contactResult:id,name'
            ])
            ->orderBy('next_contact_datetime', 'asc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Document Reason Name
    |--------------------------------------------------------------------------
    */
    private function getDocumentReasonName($reasonId)
    {
        $reason = SettingsDocumentRequestReason::find($reasonId);
        return $reason ? $reason->name : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Create Follow for Related Customer
    |--------------------------------------------------------------------------
    */
    private function createFollowForRelatedCustomer(Customer $customer, array $originalData): void
    {
        try {
            $followData = [
                'customer_id' => $customer->id,
                'contact_method_id' => $originalData['contact_method_id'],
                'contact_location' => $originalData['contact_location'] ?? null,
                'contacted_person' => $originalData['contacted_person'] ?? null,
                'contacted_person_name' => $originalData['contacted_person_name'] ?? null,
                'customer_status_id' => $originalData['customer_status_id'] ?? null,
                'contact_result_id' => $originalData['contact_result_id'],
                'notes' => $originalData['notes'] ?? null,
                'created_by' => Auth::id()
            ];

            $relatedFollow = CustomerFollow::create($followData);

            // ✅ حفظ التفاصيل الإضافية
            $this->saveContactResultDetailsForRelated($relatedFollow, $originalData);

            // ✅ تحديث حالة العميل
            $this->updateCustomerStatus($relatedFollow, $originalData);

            // Log::info('Follow created for related customer', [
            //     'customer_id' => $customer->id,
            //     'follow_id' => $relatedFollow->id,
            //     'contact_result_id' => $originalData['contact_result_id']
            // ]);

            // إنشاء مهمة جديدة إذا كانت نتيجة الاتصال تتطلب ذلك
            $this->createTaskFromFollow($relatedFollow, $originalData);
        } catch (\Exception $e) {
            // Log::error('Error creating follow for related customer: ' . $e->getMessage(), [
            //     'customer_id' => $customer->id,
            //     'error' => $e->getMessage()
            // ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save Contact Result Details for Related Customer
    |--------------------------------------------------------------------------
    */
    private function saveContactResultDetailsForRelated(CustomerFollow $customerFollow, array $data): void
    {
        $contactResultId = $data['contact_result_id'];

        try {
            switch ($contactResultId) {
                case 3: // وعد بالسداد
                    if (isset($data['promise_amount']) && isset($data['promise_date'])) {
                        PromisePaymentDetails::create([
                            'contact_result_id' => $customerFollow->id,
                            'promise_amount' => $data['promise_amount'],
                            'promise_date' => $data['promise_date'],
                        ]);
                    }
                    break;
                case 4: // معاودة الاتصال
                    if (isset($data['callback_date']) && isset($data['callback_reason'])) {
                        CallbackDetails::create([
                            'contact_result_id' => $customerFollow->id,
                            'callback_date' => $data['callback_date'],
                            'callback_reason' => $data['callback_reason'],
                            'callback_reason_other' => $data['callback_reason_other'] ?? null,
                        ]);
                    }
                    break;

                case 5: // لا يوجد رد
                case 6: // الرقم خارج الخدمة
                case 7: // الرقم غير صحيح
                case 8: // الهاتف مغلق
                    // هذه النتائج لا تحتاج تفاصيل إضافية
                    break;

                case 9: // طلب مستندات
                    if (isset($data['documents_date']) && isset($data['documents_reason'])) {
                        DocumentRequestDetails::create([
                            'contact_result_id' => $customerFollow->id,
                            'documents_date' => $data['documents_date'],
                            'documents_reason_id' => $data['documents_reason'],
                        ]);
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Log::error('Error saving contact result details for related customer: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء خطاب شركة تلقائياً بعد المتابعة
    |--------------------------------------------------------------------------
    | يتم استدعاؤها تلقائياً عند إنشاء متابعة بنتيجة محددة
    */
    private function createCompanyLetterIfNeeded(CustomerFollow $customerFollow, array $data): void
    {
        $actionTypes = [
            1 => 'payment_full',
            2 => 'payment_partial',
            9 => 'document_request',
            11 => 'recovery_request'
        ];

        if (!isset($actionTypes[$data['contact_result_id']])) {
            return;
        }

        $initialStatus = in_array($data['contact_result_id'], [1, 2])
            ? 'pending_approval'
            : 'ready_to_send';

        CompanyLetter::create([
            'customer_id'        => $customerFollow->customer_id,
            'customer_follow_id' => $customerFollow->id,
            'collection_id'      => $customerFollow->collection_id,
            'action_type'        => $actionTypes[$data['contact_result_id']],
            'status'             => $initialStatus,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Payment Plan on Update --  Payment Plan Management on Follow Update
    |--------------------------------------------------------------------------
    */
    private function handlePaymentPlanOnUpdate(CustomerFollow $customerFollow, array $data, int $oldContactResultId): void
    {
        $oldContactResultId = (int) $oldContactResultId;
        $newContactResultId = (int) $data['contact_result_id'];

        if ($newContactResultId === 3) {
            if ($oldContactResultId !== 3) {
                $this->createPaymentPlanFromPromise($customerFollow, $data);
            } else {
                $this->updatePaymentPlanFromPromise($customerFollow, $data);
            }
            return;
        }

        if ($newContactResultId === 2) {
            if (empty($data['next_partial_payment_date'])) {
                $this->deleteRelatedPaymentPlan($customerFollow);
                // ✅ حذف المهمة المرتبطة بدلاً من حذف المتابعة
                Task::where('customer_id', $customerFollow->customer_id)
                    ->where('follow_up_id', $customerFollow->id)
                    ->where('task_type', 'promise')
                    ->delete();
                return;
            }

            if ($oldContactResultId !== 2) {
                $this->createPaymentPlanFromPartialPayment($customerFollow, $data);

                // ✅ إضافة مهمة وعد بالسداد
                Task::create([
                    'customer_id' => $customerFollow->customer_id,
                    'follow_up_id' => $customerFollow->id,
                    'task_type' => 'promise',
                    'due_date' => $data['next_partial_payment_date'],
                    'status' => 'pending',
                ]);
            } else {
                $this->updatePaymentPlanFromPartialPayment($customerFollow, $data);

                // ✅ تحديث المهمة بدلاً من تحديث المتابعة
                Task::updateOrCreate(
                    [
                        'customer_id' => $customerFollow->customer_id,
                        'follow_up_id' => $customerFollow->id,
                        'task_type' => 'promise',
                    ],
                    [
                        'due_date' => $data['next_partial_payment_date'],
                        'status' => 'pending',
                    ]
                );
            }
            return;
        }

        if (in_array($oldContactResultId, [2, 3], true)) {
            $this->deleteRelatedPaymentPlan($customerFollow);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Payment Plan From Promise
    |--------------------------------------------------------------------------
    | دالة جديدة لتحديث خطة سداد بناءً على وعد السداد
    */
    private function updatePaymentPlanFromPromise(CustomerFollow $customerFollow, array $data): void
    {
        $paymentPlan = CustomerPaymentPlan::where('customer_follow_id', $customerFollow->id)->first();

        if ($paymentPlan && isset($data['promise_amount']) && isset($data['promise_date'])) {
            $this->customerPaymentPlanService->updatePaymentPlan(
                $paymentPlan,
                [
                    'due_amount' => $data['promise_amount'],
                    'due_date'   => $data['promise_date'],
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Related Payment Plan
    |--------------------------------------------------------------------------
    | دالة جديدة لحذف خطة السداد المرتبطة بالمتابعة
    */
    private function deleteRelatedPaymentPlan(CustomerFollow $customerFollow): void
    {
        $paymentPlan = CustomerPaymentPlan::where('customer_follow_id', $customerFollow->id)->first();
        if ($paymentPlan) {
            $this->customerPaymentPlanService->deletePaymentPlan($paymentPlan);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payment Plan From Promise
    |--------------------------------------------------------------------------
    | تعديل بسيط: إضافة customer_follow_id لربط خطة السداد بالمتابعة
    */
    private function createPaymentPlanFromPromise(CustomerFollow $customerFollow, array $data): void
    {
        if (isset($data['promise_amount']) && isset($data['promise_date'])) {
            $this->customerPaymentPlanService->createSinglePaymentPlan(
                $customerFollow->customer,
                [
                    'due_amount' => $data['promise_amount'],
                    'due_date'   => $data['promise_date'],
                    'customer_follow_id' => $customerFollow->id,
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payment Plan From Partial Payment
    |--------------------------------------------------------------------------
    | إنشاء خطة سداد من السداد الجزئي للمبلغ المتبقي
    */
    private function createPaymentPlanFromPartialPayment(CustomerFollow $customerFollow, array $data): void
    {
        if (empty($data['next_partial_payment_date'])) {
            return;
        }

        $customer = $customerFollow->customer?->fresh();
        if (!$customer) {
            return;
        }

        $baseDebt  = (float) ($customer->remaining_debt ?? 0);
        $paidNow   = (float) ($data['partial_payment_amount'] ?? 0);
        $dueAmount = max(0, $baseDebt - $paidNow);

        if ($dueAmount <= 0) {
            return;
        }

        $this->customerPaymentPlanService->createSinglePaymentPlan(
            $customer,
            [
                'due_amount' => $dueAmount,
                'due_date' => $data['next_partial_payment_date'],
                'customer_follow_id' => $customerFollow->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Payment Plan From Partial Payment
    |--------------------------------------------------------------------------
    | تحديث خطة سداد من السداد الجزئي
    */
    private function updatePaymentPlanFromPartialPayment(CustomerFollow $customerFollow, array $data): void
    {
        $paymentPlan = CustomerPaymentPlan::where('customer_follow_id', $customerFollow->id)->first();

        if (empty($data['next_partial_payment_date'])) {
            if ($paymentPlan) {
                $this->customerPaymentPlanService->deletePaymentPlan($paymentPlan);
            }
            return;
        }

        $customer = $customerFollow->customer?->fresh();
        if (!$customer) {
            return;
        }

        $baseDebt  = (float) ($customer->remaining_debt ?? 0);
        $paidNow   = (float) ($data['partial_payment_amount'] ?? 0);
        $dueAmount = max(0, $baseDebt - $paidNow);

        if ($dueAmount <= 0) {
            if ($paymentPlan) {
                $this->customerPaymentPlanService->deletePaymentPlan($paymentPlan);
            }
            return;
        }

        if ($paymentPlan) {
            $this->customerPaymentPlanService->updatePaymentPlan(
                $paymentPlan,
                [
                    'due_amount' => $dueAmount,
                    'due_date' => $data['next_partial_payment_date'],
                ]
            );
        } else {
            $this->customerPaymentPlanService->createSinglePaymentPlan(
                $customer,
                [
                    'due_amount' => $dueAmount,
                    'due_date' => $data['next_partial_payment_date'],
                    'customer_follow_id' => $customerFollow->id,
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Task From Follow
    |--------------------------------------------------------------------------
    */
    private function createTaskFromFollow(CustomerFollow $customerFollow, array $data): void
    {
        $contactResultId = $data['contact_result_id'];
        $taskType = null;
        $dueDate = null;

        switch ($contactResultId) {
            case 3: // وعد بالسداد
                if (isset($data['promise_date'])) {
                    $taskType = 'promise';
                    $dueDate = $data['promise_date'];
                }
                break;

            case 4: // معاودة الاتصال
                if (isset($data['callback_date'])) {
                    $taskType = 'callback';
                    $dueDate = $data['callback_date'];
                }
                break;

            case 9: // طلب مستندات
                if (isset($data['documents_date'])) {
                    $taskType = 'document_request';
                    $dueDate = $data['documents_date'];
                }
                break;
        }

        // إنشاء المهمة إذا كان النوع والتاريخ متوفرين
        if ($taskType && $dueDate) {
            Task::create([
                'customer_id' => $customerFollow->customer_id,
                'follow_up_id' => $customerFollow->id,
                'task_type' => $taskType,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Pending Tasks For Customer
    |--------------------------------------------------------------------------
    */
    private function completePendingTasksForCustomer(int $customerId): void
    {
        Task::where('customer_id', $customerId)
            ->where('status', 'pending')
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Task From Follow
    |--------------------------------------------------------------------------
    */
    private function updateTaskFromFollow(CustomerFollow $customerFollow, array $data, int $oldContactResultId): void
    {
        $newContactResultId = $data['contact_result_id'];

        // حذف المهمة القديمة إذا تغير نوع نتيجة الاتصال
        if ($oldContactResultId !== $newContactResultId) {
            Task::where('follow_up_id', $customerFollow->id)->delete();
        }

        // إنشاء مهمة جديدة إذا كانت نتيجة الاتصال تتطلب ذلك
        $this->createTaskFromFollow($customerFollow, $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Ensure No Pending Collection
    |--------------------------------------------------------------------------
    | دالة موحدة للتأكد من عدم وجود سند معلق (تستخدم في الإضافة والتعديل)
    */
    private function ensureNoPendingCollection(?int $customerId): void
    {
        if (!$customerId) {
            return;
        }

        $hasPendingCollection = CollectionCollection::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customerId)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingCollection) {
            throw new \Exception('يوجد سند سابق بانتظار الاعتماد. يرجى اعتماده أولاً.');
        }
    }
}

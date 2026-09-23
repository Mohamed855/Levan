<?php

namespace App\Services\Tenants\BusinessCenter\Customer\UpdateRequest;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Notification\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerUpdateRequestService
{
    /*
    |--------------------------------------------------------------------------
    | Protected Properties
    |--------------------------------------------------------------------------
    */
    protected NotificationService $notificationService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /*
    |--------------------------------------------------------------------------
    | Create Update Request
    |--------------------------------------------------------------------------
    */
    public function createUpdateRequest(Customer $customer, array $data): CustomerUpdateRequest
    {
        return DB::transaction(function () use ($customer, $data) {
            unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

            $updateRequest = CustomerUpdateRequest::create([
                'customer_id' => $customer->id,
                'requested_by' => Auth::id(),
                'status' => CustomerUpdateRequest::STATUS_PENDING,
                'requested_data' => $data,
            ]);
            // Notify customer about new update request -----------------------------------
            $this->notificationService->sendUpdateRequestCreatedNotification($updateRequest, $customer);
            // ------------------------------------------------------------------------------
            return $updateRequest;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Update Request
    |--------------------------------------------------------------------------
    */
    public function approveUpdateRequest(CustomerUpdateRequest $updateRequest): bool
    {
        return DB::transaction(function () use ($updateRequest) {
            if ($updateRequest->request_type === 'add_phone') {
                $phones = $updateRequest->requested_data['phones'];
                $lastPhone = end($phones);
                CustomerPhone::create([
                    'customer_id' => $updateRequest->customer_id,
                    'phone' => $lastPhone['phone'],
                    'is_primary' => $lastPhone['is_primary'] ?? false
                ]);
            } else {
                $customer = $updateRequest->customer;
                $customer->update($updateRequest->requested_data);
            }
            $updateRequest->update([
                'status' => CustomerUpdateRequest::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Notify customer about new update request -----------------------------------
            $this->notificationService->sendUpdateRequestApprovedNotification($updateRequest, $updateRequest->customer);
            // ------------------------------------------------------------------------------

            return true;
        });
    }



    /*
    |--------------------------------------------------------------------------
    | Reject Update Request
    |--------------------------------------------------------------------------
    */
    public function rejectUpdateRequest(CustomerUpdateRequest $updateRequest, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($updateRequest, $reason) {
            $updateRequest->update([
                'status' => CustomerUpdateRequest::STATUS_REJECTED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Notify customer about new update request -----------------------------------
            $this->notificationService->sendUpdateRequestRejectedNotification($updateRequest, $updateRequest->customer, $reason);
            // ------------------------------------------------------------------------------

            return true;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Approve Update Requests
    |--------------------------------------------------------------------------
    */
    public function bulkApproveRequests(array $requestIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($requestIds as $requestId) {
            try {
                $updateRequest = CustomerUpdateRequest::find($requestId);

                if (!$updateRequest) {
                    $results['failed']++;
                    $results['errors'][$requestId] = 'الطلب غير موجود';
                    continue;
                }

                if ($updateRequest->status !== CustomerUpdateRequest::STATUS_PENDING) {
                    $results['failed']++;
                    $results['errors'][$requestId] = 'الطلب ليس في حالة معلقة';
                    continue;
                }

                $this->approveUpdateRequest($updateRequest);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$requestId] = $e->getMessage();
            }
        }

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Reject Update Requests
    |--------------------------------------------------------------------------
    */
    public function bulkRejectRequests(array $requestIds, ?string $reason = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($requestIds as $requestId) {
            try {
                $updateRequest = CustomerUpdateRequest::find($requestId);

                if (!$updateRequest) {
                    $results['failed']++;
                    $results['errors'][$requestId] = 'الطلب غير موجود';
                    continue;
                }

                if ($updateRequest->status !== CustomerUpdateRequest::STATUS_PENDING) {
                    $results['failed']++;
                    $results['errors'][$requestId] = 'الطلب ليس في حالة معلقة';
                    continue;
                }

                $this->rejectUpdateRequest($updateRequest, $reason);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$requestId] = $e->getMessage();
            }
        }

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Pending Requests Count
    |--------------------------------------------------------------------------
    */
    public function getPendingRequestsCount(): int
    {
        return CustomerUpdateRequest::pending()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Pending Requests
    |--------------------------------------------------------------------------
    */
    public function getUserPendingRequests(int $userId)
    {
        return CustomerUpdateRequest::with(['customer', 'approvedBy'])
            ->where('requested_by', $userId)
            ->pending()
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Pending Requests
    |--------------------------------------------------------------------------
    */
    public function getAllPendingRequests()
    {
        return CustomerUpdateRequest::with(['customer', 'requestedBy', 'approvedBy'])
            ->pending()
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Request Details
    |--------------------------------------------------------------------------
    */
    public function getRequestDetails(int $requestId)
    {
        return CustomerUpdateRequest::with(['customer', 'requestedBy', 'approvedBy'])
            ->find($requestId);
    }

    /*
    |--------------------------------------------------------------------------
    | Check if user can update customer directly
    |--------------------------------------------------------------------------
    */
    public function canUpdateDirectly(User $user): bool
    {
        return $user->can('تحديث مباشر للبيانات');
    }

    /*
    |--------------------------------------------------------------------------
    | Compare customer data with update request data
    |--------------------------------------------------------------------------
    */
    public function compareData(Customer $customer, array $newData): array
    {
        $changes = [];

        if (isset($newData['phones']) && is_array($newData['phones'])) {
            $currentPhones = $customer->phones()->pluck('phone')->toArray();
            $newPhones = array_column($newData['phones'], 'phone');

            foreach ($newPhones as $phone) {
                if (!in_array($phone, $currentPhones)) {
                    $changes['phones'][] = [
                        'old' => null,
                        'new' => $phone,
                        'type' => 'add_phone'
                    ];
                }
            }
        }

        $currentData = $customer->toArray();
        foreach ($newData as $key => $value) {
            if ($key !== 'phones' && array_key_exists($key, $currentData)) {
                if ($currentData[$key] != $value) {
                    $changes[$key] = [
                        'old' => $currentData[$key],
                        'new' => $value
                    ];
                }
            }
        }

        return $changes;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Requests Statistics For Index Page
    |--------------------------------------------------------------------------
    */
    public function getRequestsStatistics(): array
    {
        return [
            'total' => CustomerUpdateRequest::count(),
            'pending' => CustomerUpdateRequest::pending()->count(),
            'approved' => CustomerUpdateRequest::approved()->count(),
            'rejected' => CustomerUpdateRequest::rejected()->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Filtered Requests Query
    |--------------------------------------------------------------------------
    */
    public function getFilteredRequestsQuery(array $filters = [])
    {
        $query = CustomerUpdateRequest::with([
            'customer:id,name,identity_number',
            'requestedBy:id,name',
            'approvedBy:id,name'
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }

        if (!empty($filters['search_query'])) {
            $searchTerm = $filters['search_query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('identity_number', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('requestedBy', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }
}

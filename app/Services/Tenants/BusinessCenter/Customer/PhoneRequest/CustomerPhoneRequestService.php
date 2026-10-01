<?php

namespace App\Services\Tenants\BusinessCenter\Customer\PhoneRequest;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Services\Tenants\Notification\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class CustomerPhoneRequestService
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
    | Check Update Permission
    |--------------------------------------------------------------------------
    | التحقق مما إذا كان المستخدم لديه صلاحية التعديل المباشر
    */
    public function canUpdateDirectly(): bool
    {
        return Auth::user()->can('customers.update_directly');
    }

    /*
    |--------------------------------------------------------------------------
    | Add Phone Directly
    |--------------------------------------------------------------------------
    | إضافة رقم هاتف مباشرة للعميل بدون الحاجة لموافقة
    | مع التحقق من صحة الرقم وعدم تكراره
    */
    public function addPhoneDirectly(int $customerId, array $phoneData): CustomerPhone
    {
        return DB::transaction(function () use ($customerId, $phoneData) {
            $cleanedPhone = $this->cleanPhoneNumber($phoneData['phone']);

            if ($this->phoneExists($customerId, $cleanedPhone)) {
                throw new \InvalidArgumentException('رقم الهاتف مسجل مسبقاً');
            }

            CustomerPhone::where('customer_id', $customerId)
                ->update(['is_primary' => false]);

            return CustomerPhone::create([
                'customer_id' => $customerId,
                'phone' => $cleanedPhone,
                'is_primary' => true
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Create Phone Update Request
    |--------------------------------------------------------------------------
    | إنشاء طلب تحديث لإضافة رقم هاتف جديد يحتاج لموافقة
    | مع التحقق من صحة الرقم وعدم تكراره
    */
    public function createPhoneUpdateRequest(int $customerId, array $phoneData): CustomerUpdateRequest
    {
        return DB::transaction(function () use ($customerId, $phoneData) {
            $customer = Customer::find($customerId);
            $cleanedPhone = $this->cleanPhoneNumber($phoneData['phone']);

            if ($this->phoneExists($customerId, $cleanedPhone)) {
                throw new \InvalidArgumentException('رقم الهاتف مسجل مسبقاً');
            }

            if ($this->phoneExistsInUserPendingRequests($customerId, $cleanedPhone, Auth::id())) {
                throw new \InvalidArgumentException('لديك طلب مقدم مسبقاً لإضافة هذا الرقم');
            }

            $currentPhones = $customer->phones()->get()->map(function ($phone) {
                return [
                    'customer_id' => $phone->customer_id,
                    'phone' => $phone->phone,
                    'is_primary' => false
                ];
            })->toArray();

            $newPhone = [
                'customer_id' => $customerId,
                'phone' => $cleanedPhone,
                'is_primary' => true
            ];

            $updatedPhones = array_merge($currentPhones, [$newPhone]);

            $updateRequest = CustomerUpdateRequest::create([
                'customer_id' => $customerId,
                'requested_by' => Auth::id(),
                'status' => CustomerUpdateRequest::STATUS_PENDING,
                'requested_data' => [
                    'phones' => $updatedPhones
                ],
                'request_type' => 'add_phone'
            ]);

            $this->notificationService->sendPhoneRequestCreatedNotification($updateRequest, $customer);

            return $updateRequest;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Clean Phone Number
    |--------------------------------------------------------------------------
    | تنظيف رقم الهاتف من أي أحرف غير أرقام أو علامة +
    | مع التحقق من أن الرقم سعودي وصحيح
    */
    private function cleanPhoneNumber(string $phone): string
    {
        // إزالة أي أحرف غير أرقام أو علامة +
        $cleaned = preg_replace('/[^0-9+]/', '', trim($phone));

        // التحقق من أن الرقم يحتوي على أرقام فقط بعد التنظيف
        if (!preg_match('/^[0-9+]+$/', $cleaned)) {
            throw new \InvalidArgumentException('يجب إدخال رقم هاتف صحيح');
        }

        // التحقق من أن الرقم سعودي
        if (!$this->isValidSaudiPhoneNumber($cleaned)) {
            throw new \InvalidArgumentException('يجب إدخال رقم هاتف صحيح');
        }

        // توحيد صيغة الرقم السعودي
        $cleaned = $this->normalizeSaudiPhoneNumber($cleaned);

        // التحقق من طول الرقم بعد التوحيد
        if (strlen($cleaned) !== 12) {
            throw new \InvalidArgumentException('يجب إدخال رقم هاتف صحيح');
        }

        return $cleaned;
    }

    /*
    |--------------------------------------------------------------------------
    | Check if Phone Exists
    |--------------------------------------------------------------------------
    | التحقق من أن رقم الهاتف غير مكرر للعميل
    */
    private function phoneExists(int $customerId, string $phone): bool
    {
        return CustomerPhone::where('customer_id', $customerId)
            ->where('phone', $phone)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Check if Phone Exists in User Pending Requests
    |--------------------------------------------------------------------------
    | التحقق من أن رقم الهاتف غير مكرر في طلبات المستخدم المعلقة
    */
    private function phoneExistsInUserPendingRequests(int $customerId, string $phone, int $userId): bool
    {
        $pendingRequests = CustomerUpdateRequest::where('customer_id', $customerId)
            ->where('requested_by', $userId)
            ->where('status', CustomerUpdateRequest::STATUS_PENDING)
            ->where('request_type', 'add_phone')
            ->get();

        foreach ($pendingRequests as $request) {
            if (isset($request->requested_data['phones'])) {
                $phones = $request->requested_data['phones'];
                foreach ($phones as $phoneData) {
                    if ($phoneData['phone'] === $phone) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Saudi Phone Number
    |--------------------------------------------------------------------------
    | التحقق من أن رقم الهاتف سعودي صحيح
    */
    private function isValidSaudiPhoneNumber(string $phone): bool
    {
        $localNumber = preg_replace('/^(966|\+966)/', '', $phone);

        if (str_starts_with($localNumber, '0')) {
            $localNumber = substr($localNumber, 1);
        }

        return preg_match('/^5[0-9]{8}$/', $localNumber);
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Saudi Phone Number
    |--------------------------------------------------------------------------
    | توحيد صيغة الرقم السعودي بإضافة كود البلد
    */
    private function normalizeSaudiPhoneNumber(string $phone): string
    {
        if (preg_match('/^(\+?966)(5[0-9]{8})$/', $phone, $matches)) {
            return '966' . $matches[2];
        }

        if (preg_match('/^(5[0-9]{8})$/', $phone)) {
            return '966' . $phone;
        }

        if (preg_match('/^(05[0-9]{8})$/', $phone)) {
            return '966' . substr($phone, 1);
        }

        return $phone;
    }
}

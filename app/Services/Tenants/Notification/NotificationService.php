<?php

namespace App\Services\Tenants\Notification;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Tenants\General\GeneralNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /*
    |--------------------------------------------------------------------------
    | Send Collection Created Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند إنشاء تحصيل جديد (سداد كامل أو جزئي) للمشرفين والمدراء
    */
    public function sendCollectionCreatedNotification($collection, $customer)
    {
        // الحصول على اسم الموظف الذي أضاف التحصيل
        $staffName = $collection->addedBy ? $collection->addedBy->name : 'موظف';

        $title = 'تحصيل جديد';
        $message = "تم إضافة تحصيل للعميل {$customer->name} بقيمة {$collection->collected_amount} من قبل الموظف {$staffName}";
        $type = 'info';
        $icon = 'ti ti-cash';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('financial-collections.collections.show', $collection->id, '/financial-collections/collections');

        // إرسال الإشعار للمشرفين والمدراء فقط
        $this->sendNotificationToRoles(['Admin', 'Supervisor'], $title, $message, $type, $icon, $url);
    }

    /*
    |--------------------------------------------------------------------------
    | Send Collection Approved Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند اعتماد تحصيل لموظف التحصيل
    */
    public function sendCollectionApprovedNotification($collection, $customer)
    {
        $title = 'تم اعتماد التحصيل';
        $message = "تم اعتماد تحصيلك للعميل {$customer->name} بقيمة {$collection->collected_amount}";
        $type = 'success';
        $icon = 'ti ti-check';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('financial-collections.collections.show', $collection->id, '/financial-collections/collections');

        // إرسال الإشعار لموظف التحصيل الذي قام بإضافة التحصيل
        if ($collection->addedBy) {
            $collection->addedBy->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Collection Rejected Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند رفض تحصيل لموظف التحصيل
    */
    public function sendCollectionRejectedNotification($collection, $customer, $reason)
    {
        $title = 'تم رفض التحصيل';
        $message = "تم رفض تحصيلك للعميل {$customer->name} بقيمة {$collection->collected_amount}. السبب: {$reason}";
        $type = 'danger';
        $icon = 'ti ti-x';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('financial-collections.collections.show', $collection->id, '/financial-collections/collections');

        // إرسال الإشعار لموظف التحصيل الذي قام بإضافة التحصيل
        if ($collection->addedBy) {
            $collection->addedBy->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Update Request Created Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند إنشاء طلب تحديث بيانات للمشرفين والمدراء
    */
    public function sendUpdateRequestCreatedNotification($updateRequest, $customer)
    {
        // الحصول على اسم الموظف الذي قدم الطلب
        $staffName = $updateRequest->requestedBy ? $updateRequest->requestedBy->name : 'موظف';

        $title = 'طلب تحديث بيانات جديد';
        $message = "تم تقديم طلب تحديث بيانات للعميل {$customer->name} من قبل الموظف {$staffName}";
        $type = 'info';
        $icon = 'ti ti-file-description';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('customers.update-requests.show', $updateRequest->id, '/customers/update-requests');

        // إرسال الإشعار للمشرفين والمدراء فقط
        $this->sendNotificationToRoles(['Admin', 'Supervisor'], $title, $message, $type, $icon, $url);
    }

    /*
    |--------------------------------------------------------------------------
    | Send Update Request Approved Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند اعتماد طلب تحديث بيانات للموظف الذي قدم الطلب
    */
    public function sendUpdateRequestApprovedNotification($updateRequest, $customer)
    {
        $title = 'تم اعتماد طلب تحديث البيانات';
        $message = "تم اعتماد طلب تحديث بيانات العميل {$customer->name}";
        $type = 'success';
        $icon = 'ti ti-check';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('customers.update-requests.show', $updateRequest->id, '/customers/update-requests');

        // إرسال الإشعار للموظف الذي قدم الطلب
        if ($updateRequest->requestedBy) {
            $updateRequest->requestedBy->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Update Request Rejected Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند رفض طلب تحديث بيانات للموظف الذي قدم الطلب
    */
    public function sendUpdateRequestRejectedNotification($updateRequest, $customer, $reason)
    {
        $title = 'تم رفض طلب تحديث البيانات';
        $message = "تم رفض طلب تحديث بيانات العميل {$customer->name}. السبب: {$reason}";
        $type = 'danger';
        $icon = 'ti ti-x';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('customers.update-requests.show', $updateRequest->id, '/customers/update-requests');

        // إرسال الإشعار للموظف الذي قدم الطلب
        if ($updateRequest->requestedBy) {
            $updateRequest->requestedBy->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Phone Request Created Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند إنشاء طلب إضافة رقم هاتف للمشرفين والمدراء
    */
    public function sendPhoneRequestCreatedNotification($updateRequest, $customer)
    {
        // الحصول على اسم الموظف الذي قدم الطلب
        $staffName = $updateRequest->requestedBy ? $updateRequest->requestedBy->name : 'موظف';

        $title = 'طلب إضافة رقم هاتف جديد';
        $message = "تم تقديم طلب إضافة رقم هاتف جديد للعميل {$customer->name} من قبل الموظف {$staffName}";
        $type = 'info';
        $icon = 'ti ti-phone';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('customers.update-requests.show', $updateRequest->id, '/customers/update-requests');

        // إرسال الإشعار للمشرفين والمدراء فقط
        $this->sendNotificationToRoles(['Admin', 'Supervisor'], $title, $message, $type, $icon, $url);
    }

    /*
    |--------------------------------------------------------------------------
    | Send Phone Request Approved Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند اعتماد طلب إضافة رقم هاتف للموظف الذي قدم الطلب
    */
    public function sendPhoneRequestApprovedNotification($updateRequest, $customer)
    {
        $title = 'تم اعتماد طلب إضافة رقم الهاتف';
        $message = "تم اعتماد طلب إضافة رقم هاتف للعميل {$customer->name}";
        $type = 'success';
        $icon = 'ti ti-phone-check';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('customers.update-requests.show', $updateRequest->id, '/customers/update-requests');

        // إرسال الإشعار للموظف الذي قدم الطلب
        if ($updateRequest->requestedBy) {
            $updateRequest->requestedBy->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Phone Request Rejected Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند رفض طلب إضافة رقم هاتف للموظف الذي قدم الطلب
    */
    public function sendPhoneRequestRejectedNotification($updateRequest, $customer, $reason)
    {
        $title = 'تم رفض طلب إضافة رقم الهاتف';
        $message = "تم رفض طلب إضافة رقم هاتف للعميل {$customer->name}. السبب: {$reason}";
        $type = 'danger';
        $icon = 'ti ti-phone-x';

        // استخدام اسم المسار الصحيح
        $url = $this->getRouteUrl('customers.update-requests.show', $updateRequest->id, '/customers/update-requests');

        // إرسال الإشعار للموظف الذي قدم الطلب
        if ($updateRequest->requestedBy) {
            $updateRequest->requestedBy->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Route URL
    |--------------------------------------------------------------------------
    | الحصول على رابط المسار مع معالجة الأخطاء
    */
    private function getRouteUrl($routeName, $id, $fallbackUrl)
    {
        try {
            return tenant_route_name($routeName, $id);
        } catch (\Exception $e) {
            return url($fallbackUrl);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Notification To Roles
    |--------------------------------------------------------------------------
    | إرسال إشعار لجميع المستخدمين الذين لديهم أدوار محددة
    */
    private function sendNotificationToRoles(array $roles, $title, $message, $type, $icon, $url)
    {
        $users = User::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        })->get();

        foreach ($users as $user) {
            $user->notify(new GeneralNotification($title, $message, $type, $icon, $url));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Send Mention Notification
    |--------------------------------------------------------------------------
    | إرسال إشعار عند إشارة أحد الموظفين لآخر في تعليق
    | هذه الدالة مصممة لتكون مرنة وقابلة لإعادة الاستخدام
    */
    public function sendMentionNotification(
        User $mentionedUser,
        User $author,
        string $customerName,
        int $customerId
    ): void {
        try {
            // 1. تجنب إرسال إشعار للمستخدم نفسه
            if ($author->id === $mentionedUser->id) {
                // Log::info('Mention notification skipped: User mentioned themselves.', [
                //     'author_id' => $author->id,
                //     'mentioned_user_id' => $mentionedUser->id,
                // ]);
                return;
            }

            // 2. التحقق من أن المستخدم المذكور نشط
            if ($mentionedUser->status !== 'active') {
                // Log::warning('Mention notification skipped: User is not active.', [
                //     'mentioned_user_id' => $mentionedUser->id,
                //     'status' => $mentionedUser->status
                // ]);
                return;
            }

            // 3. تجهيز بيانات الإشعار
            $title = 'إشارة جديدة في تعليق';
            $message = "أشار إليك {$author->name} في تعليق على العميل: {$customerName}";
            $type = 'mention';
            $icon = 'ti ti-flag';

            // 4. إنشاء الرابط
            $url = $this->getRouteUrl('customers.show', $customerId, "/customers/{$customerId}");

            // 5. إرسال الإشعار
            $mentionedUser->notify(new GeneralNotification($title, $message, $type, $icon, $url));

            // 6. تسجيل النجاح
            // Log::info('Mention notification sent successfully.', [
            //     'from_user_id' => $author->id,
            //     'to_user_id' => $mentionedUser->id,
            //     'customer_id' => $customerId,
            //     'notification_type' => $type
            // ]);
        } catch (\Exception $e) {
            // Log::error('Failed to send mention notification.', [
            //     'from_user_id' => $author->id ?? null,
            //     'to_user_id' => $mentionedUser->id ?? null,
            //     'customer_id' => $customerId ?? null,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString(),
            // ]);

            // لا نرمي الخطأ حتى لا نعطل العملية الأساسية
        }
    }
}

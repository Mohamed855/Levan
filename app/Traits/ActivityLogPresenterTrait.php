<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Collection;

trait ActivityLogPresenterTrait
{
    protected function activityLogNameMap(): array
    {
        return [
            'companies' => 'الشركات',
            'users' => 'المستخدمين',
            'admin_users' => 'إدارة المستخدمين',
            'auth' => 'تسجيل الدخول',
            'roles' => 'الأدوار والصلاحيات',
            'permissions' => 'الصلاحيات',
            'properties' => 'العقارات',
            'assignments' => 'الإسنادات',
            'auctions' => 'المزادات',
            'tickets' => 'تذاكر الدعم',
            'bills' => 'الفواتير',
            'subscriptions' => 'الاشتراكات',
            'settings' => 'الإعدادات',
            'activity_logs' => 'سجل النشاطات',
            'default' => 'عام',
        ];
    }

    protected function activitySubjectTypeMap(): array
    {
        return [
            'Company' => 'شركة',
            'User' => 'مستخدم',
            'AdminUser' => 'مشرف نظام',
            'Role' => 'دور',
            'Permission' => 'صلاحية',
            'Property' => 'عقار',
            'Assignment' => 'إسناد',
            'Auction' => 'مزاد',
            'Ticket' => 'تذكرة',
            'Bill' => 'فاتورة',
            'Subscription' => 'اشتراك',
        ];
    }

    protected function activityEventMap(): array
    {
        return [
            'created' => 'إنشاء',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'restored' => 'استعادة',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'failed_login' => 'فشل الدخول',
        ];
    }

    protected function activityEventBadgeClass(?string $event): string
    {
        return match ($event) {
            'created' => 'bg-success',
            'updated' => 'bg-info',
            'deleted' => 'bg-danger',
            'restored' => 'bg-warning',
            'login' => 'bg-primary',
            'logout' => 'bg-secondary',
            'failed_login' => 'bg-danger',
            default => 'bg-primary',
        };
    }

    protected function activityFieldTranslations(): array
    {
        return [
            'name' => 'الاسم',
            'first_name' => 'الاسم الأول',
            'last_name' => 'اسم العائلة',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'mobile' => 'الجوال',
            'position' => 'المنصب',
            'status' => 'الحالة',
            'role' => 'الدور',
            'roles' => 'الأدوار',
            'permissions' => 'الصلاحيات',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'remember_token' => 'رمز التذكر',
            'two_factor_secret' => 'المصادقة الثنائية',
            'two_factor_recovery_codes' => 'رموز الاسترجاع',
            'logo_path' => 'الشعار',
            'image' => 'الصورة',
            'avatar' => 'الصورة الرمزية',
            'background_image' => 'صورة الخلفية',
            'company_id' => 'الشركة',
            'contract_number' => 'رقم العقد',
            'start_date' => 'تاريخ البداية',
            'duration_months' => 'المدة بالأشهر',
            'end_date' => 'تاريخ النهاية',
            'auto_renewal' => 'التجديد التلقائي',
            'commission_type' => 'نوع العمولة',
            'notes' => 'الملاحظات',
            'description' => 'الوصف',
            'title' => 'العنوان',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            'deleted_at' => 'تاريخ الحذف',
            'due_date' => 'تاريخ الاستحقاق',
            'due_amount' => 'مبلغ الاستحقاق',
            'amount' => 'المبلغ',
            'price' => 'السعر',
            'area' => 'المساحة',
            'city' => 'المدينة',
            'district' => 'الحي',
            'property_number' => 'رقم الأصل',
            'auction_number' => 'رقم المزاد',
            'assignment_number' => 'رقم الإسناد',
            'deed_number' => 'رقم الصك',
            'plan_number' => 'رقم المخطط',
            'parcel_number' => 'رقم القطعة',
            'is_active' => 'نشط؟',
            'is_primary' => 'رقم أساسي؟',
        ];
    }

    public function isSensitiveField(string $field): bool
    {
        $fieldLower = strtolower($field);
        $sensitiveKeys = [
            'password',
            'password_hash',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'token',
            'secret',
            'api_key',
            'access_token',
            'refresh_token',
            'bearer_token',
        ];

        if (in_array($fieldLower, $sensitiveKeys, true)) {
            return true;
        }

        foreach (['password', 'secret', 'two_factor'] as $keyword) {
            if (str_contains($fieldLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function getSafeFieldLabel(?string $modelClass, string $field): string
    {
        if ($this->isSensitiveField($field)) {
            return $this->activityFieldTranslations()[$field] ?? 'كلمة المرور / سر أمني';
        }

        if ($modelClass && class_exists($modelClass) && method_exists($modelClass, 'getFieldLabel')) {
            try {
                $label = $modelClass::getFieldLabel($field);
                if ($label && $label !== ucfirst(str_replace('_', ' ', $field))) {
                    return (string) $label;
                }
            } catch (\Throwable) {
                // Ignore failure in subject model helper
            }
        }

        return $this->activityFieldTranslations()[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    public function getSafeValueDisplay(?string $modelClass, string $field, mixed $value): array
    {
        if ($this->isSensitiveField($field)) {
            return [
                'type' => 'sensitive',
                'display' => '[محجوب للأمان]',
                'badge' => 'bg-danger',
            ];
        }

        if ($value === null || $value === '') {
            return [
                'type' => 'null',
                'display' => '-',
            ];
        }

        if (is_bool($value)) {
            return [
                'type' => 'bool',
                'display' => $value ? 'نعم' : 'لا',
                'badge' => $value ? 'bg-success' : 'bg-secondary',
            ];
        }

        if (is_array($value) || $value instanceof Collection) {
            $arrayVal = $value instanceof Collection ? $value->toArray() : $value;
            return [
                'type' => 'array',
                'display' => json_encode($arrayVal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'raw' => $arrayVal,
            ];
        }

        $strValue = (string) $value;

        // Check if value is a file/image
        $lowerStr = strtolower($strValue);
        $imageExtensions = ['.jpg', '.jpeg', '.png', '.webp', '.gif', '.svg'];
        $isImage = false;
        foreach ($imageExtensions as $ext) {
            if (str_ends_with($lowerStr, $ext)) {
                $isImage = true;
                break;
            }
        }

        $fileKeys = ['logo_path', 'image', 'avatar', 'background_image', 'file_path', 'attachment'];
        $isFileKey = in_array(strtolower($field), $fileKeys, true);

        if ($isImage || $isFileKey) {
            $url = null;
            if (function_exists('tasset')) {
                $url = tasset($strValue);
            } elseif (function_exists('asset')) {
                $url = asset('storage/' . $strValue);
            }

            return [
                'type' => 'file',
                'is_image' => $isImage,
                'path' => $strValue,
                'url' => $url,
                'display' => basename($strValue),
            ];
        }

        if ($modelClass && class_exists($modelClass) && method_exists($modelClass, 'getValueLabel')) {
            try {
                $label = $modelClass::getValueLabel($field, $value);
                if ($label && $label !== $strValue) {
                    return [
                        'type' => 'text',
                        'display' => (string) $label,
                    ];
                }
            } catch (\Throwable) {
                // Ignore failure
            }
        }

        return [
            'type' => 'text',
            'display' => $strValue,
        ];
    }

    public function prepareChangesData($activity): array
    {
        $properties = $activity->properties;
        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }
        if (!is_array($properties)) {
            $properties = [];
        }

        $oldData = $properties['old'] ?? [];
        if ($oldData instanceof Collection) {
            $oldData = $oldData->toArray();
        }
        if (!is_array($oldData)) {
            $oldData = [];
        }

        $newData = $properties['attributes'] ?? $properties;
        if ($newData instanceof Collection) {
            $newData = $newData->toArray();
        }
        if (!is_array($newData)) {
            $newData = [];
        }

        $hasOld = !empty($oldData) || isset($properties['old']);

        $allFieldKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));
        $allFieldKeys = array_values(array_filter($allFieldKeys, fn($k) => !in_array($k, ['old', 'attributes'], true)));

        $modelClass = $activity->subject_type;
        $fields = [];

        foreach ($allFieldKeys as $field) {
            $label = $this->getSafeFieldLabel($modelClass, (string) $field);
            $oldVal = array_key_exists($field, $oldData) ? $oldData[$field] : null;
            $newVal = array_key_exists($field, $newData) ? $newData[$field] : null;

            $fields[] = [
                'key' => (string) $field,
                'label' => $label,
                'has_old' => array_key_exists($field, $oldData),
                'has_new' => array_key_exists($field, $newData),
                'old' => $this->getSafeValueDisplay($modelClass, (string) $field, $oldVal),
                'new' => $this->getSafeValueDisplay($modelClass, (string) $field, $newVal),
            ];
        }

        $sanitizedProperties = $this->sanitizePropertiesForDisplay($properties);
        $rawJson = json_encode($sanitizedProperties, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return [
            'has_changes' => !empty($fields),
            'has_old' => $hasOld,
            'fields' => $fields,
            'raw_json' => $rawJson,
        ];
    }

    public function sanitizePropertiesForDisplay(mixed $properties): array
    {
        if ($properties instanceof Collection) {
            $properties = $properties->toArray();
        }

        if (!is_array($properties)) {
            return [];
        }

        return $this->maskSensitiveRecursive($properties);
    }

    private function maskSensitiveRecursive(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $keyStr = (string) $key;
            if ($this->isSensitiveField($keyStr)) {
                $sanitized[$key] = '[محجوب للأمان]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->maskSensitiveRecursive($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function activityLogNameLabel(?string $logName): string
    {
        return $this->activityLogNameMap()[$logName] ?? ($logName ?? '-');
    }

    protected function activitySubjectDisplay($activity): string
    {
        if ($activity->log_name === 'auth') {
            $identifier = data_get($activity->properties, 'identifier', 'غير معروف');

            $subject = null;
            if ($activity->subject_type && class_exists($activity->subject_type)) {
                try {
                    $subject = $activity->subject;
                } catch (\Throwable) {
                    $subject = null;
                }
            }

            return $subject
                ? 'محاولة دخول: ' . $subject->name . ' (' . $identifier . ')'
                : 'محاولة دخول: ' . $identifier;
        }

        if (!$activity->subject_type) {
            return 'غير محدد';
        }

        if (!class_exists($activity->subject_type)) {
            return class_basename($activity->subject_type) . ' #' . $activity->subject_id . ' (محذوف)';
        }

        try {
            $subject = $activity->subject;
        } catch (\Throwable) {
            $subject = null;
        }

        if (!$subject) {
            return class_basename($activity->subject_type) . ' #' . $activity->subject_id . ' (محذوف)';
        }

        $subjectType = class_basename($activity->subject_type);
        $arabicType = $this->activitySubjectTypeMap()[$subjectType] ?? $subjectType;

        return $arabicType . ' #' . $activity->subject_id;
    }

    protected function parseActivityUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return ['os' => 'غير معروف', 'browser' => 'غير معروف', 'device' => 'غير معروف'];
        }

        $os = match (true) {
            str_contains($userAgent, 'Windows NT 10') || str_contains($userAgent, 'Windows NT 11') => 'Windows 10/11',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'غير معروف',
        };

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'Chrome/') => 'Google Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'غير معروف',
        };

        $device = match (true) {
            str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet') => 'جهاز لوحي',
            str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'Android') => 'جوال',
            default => 'حاسوب',
        };

        return compact('os', 'browser', 'device');
    }

    protected function getActivityConnectionInfo($activity): ?array
    {
        $properties = $activity->properties;

        if (!$properties || !$properties->has('ip')) {
            return null;
        }

        $userAgent = $properties->get('user_agent');
        $device = $this->parseActivityUserAgent($userAgent);

        return [
            'ip' => $properties->get('ip', 'غير معروف'),
            'reason' => $properties->get('reason'),
            'identifier' => $properties->get('identifier'),
            'user_agent' => $userAgent,
            'device' => $device['device'],
            'os' => $device['os'],
            'browser' => $device['browser'],
        ];
    }
}

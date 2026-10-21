<?php

declare(strict_types=1);

namespace App\Traits;

trait ActivityLogPresenterTrait
{
    protected function activityLogNameMap(): array
    {
        return [
            'companies'   => 'الشركات',
            'users'       => 'المستخدمين',
            'admin_users' => 'إدارة المستخدمين',
            'auth'        => 'تسجيل الدخول',
            'system'      => 'النظام العام',
            'settings'    => 'الإعدادات',
            'plans'       => 'الباقات',
            'tenants'     => 'المستأجرين',
        ];
    }

    protected function activitySubjectTypeMap(): array
    {
        return [
            'Company'     => 'شركة',
            'User'        => 'مستخدم',
            'AdminUser'   => 'مشرف إداري',
            'Tenant'      => 'مستأجر',
            'Plan'        => 'باقة',
            'Coupon'      => 'كوبون',
            'Bill'        => 'فاتورة',
            'Ticket'      => 'تذكرة',
            'Bank'        => 'حساب بنكي',
        ];
    }

    protected function activityLogNameLabel(?string $logName): string
    {
        return $this->activityLogNameMap()[$logName] ?? ($logName ?? '-');
    }

    protected function activitySubjectDisplay($activity): string
    {
        if ($activity->log_name === 'auth') {
            $identifier = data_get($activity->properties, 'identifier', 'غير معروف');

            return $activity->subject
                ? 'محاولة دخول: ' . ($activity->subject->name ?? '') . ' (' . $identifier . ')'
                : 'محاولة دخول: ' . $identifier;
        }

        if (! $activity->subject && ! $activity->subject_type) {
            return 'النظام';
        }

        if (! $activity->subject) {
            return 'سجل محذوف';
        }

        $subjectType = class_basename($activity->subject_type);
        $arabicType = $this->activitySubjectTypeMap()[$subjectType] ?? $subjectType;

        return $arabicType . ' #' . $activity->subject_id;
    }

    protected function parseActivityUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
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

        if (! $properties || ! is_iterable($properties) || ! data_get($properties, 'ip')) {
            return null;
        }

        $userAgent = data_get($properties, 'user_agent');
        $device = $this->parseActivityUserAgent($userAgent);

        return [
            'ip'         => data_get($properties, 'ip', 'غير معروف'),
            'reason'     => data_get($properties, 'reason'),
            'identifier' => data_get($properties, 'identifier'),
            'user_agent' => $userAgent,
            'device'     => $device['device'],
            'os'         => $device['os'],
            'browser'    => $device['browser'],
        ];
    }
}

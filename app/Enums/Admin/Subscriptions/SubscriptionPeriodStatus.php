<?php

namespace App\Enums\Admin\Subscriptions;

enum SubscriptionPeriodStatus: string
{
    case ACTIVE = 'active';      // الفترة النشطة الحالية
    case QUEUED = 'queued';      // فترة مجدولة للتفعيل لاحقاً
    case ENDED = 'ended';       // فترة انتهت بشكل طبيعي
    case CANCELLED = 'cancelled';   // فترة ملغاة
    case PENDING_PAYMENT = 'pending_payment'; // في انتظار الدفع

    case TRIAL = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشط',
            self::QUEUED => 'مجدول',
            self::ENDED => 'منتهي',
            self::CANCELLED => 'ملغي',
            self::PENDING_PAYMENT => 'في انتظار الدفع',

            self::TRIAL => 'فترة تجربة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::QUEUED => 'info',
            self::ENDED => 'secondary',
            self::CANCELLED => 'danger',
            self::PENDING_PAYMENT => 'warning',

            self::TRIAL => 'dark',
        };
    }

    // public function isActive(): bool
    // {
    //     return $this === self::ACTIVE;
    // }

    // public function isQueued(): bool
    // {
    //     return $this === self::QUEUED;
    // }

    // public function isEnded(): bool
    // {
    //     return in_array($this, [self::ENDED]);
    // }
}

<?php

namespace App\Enums\Admin\Billing;

enum BillStatus: string
{
    case UNPAID                 = 'unpaid';          // لم يتم الدفع
    case PAID                   = 'paid';            // تم الدفع بالكامل
    case PARTIALLY_PAID         = 'partially_paid';  // دفع جزئي
    case OVERDUE                = 'overdue';         // متأخر عن موعد الدفع
    case REFUNDED               = 'refunded';        // تم استرجاع المبلغ
    case PENDING                = 'pending';         // قيد المعالجة
    case PENDING_VERIFICATION   = 'pending_verification'; // في انتظار التحقق
    case REJECTED               = 'rejected';        // تم رفض الإيصال المرفوع
    case CANCELLED              = 'cancelled';        // تم إلغاء الإيصال

    case TRIAL = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID         => 'لم يتم الدفع',
            self::PAID           => 'مدفوع',
            self::PARTIALLY_PAID => 'دفع جزئي',
            self::OVERDUE        => 'متأخر',
            self::REFUNDED       => 'مسترجع',
            self::PENDING        => 'قيد المعالجة',
            self::PENDING_VERIFICATION => 'في انتظار التحقق',
            self::REJECTED       => 'مرفوض',
            self::CANCELLED      => "تم الإلغاء",
            self::TRIAL     => 'فترة تجربة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UNPAID         => 'warning',
            self::PAID           => 'success',
            self::PARTIALLY_PAID => 'info',
            self::OVERDUE        => 'danger',
            self::REFUNDED       => 'dark',
            self::PENDING        => 'secondary',
            self::PENDING_VERIFICATION => 'info',
            self::REJECTED       => 'danger',
            self::CANCELLED       => 'danger',
            self::TRIAL       => 'danger',
        };
    }
}

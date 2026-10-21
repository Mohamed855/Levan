<?php

declare(strict_types=1);

namespace App\Enums\Admin\Bills;

enum BillStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case REFUNDED = 'refunded';
    case PENDING = 'pending';
    case PENDING_VERIFICATION = 'pending_verification';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case TRIAL = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'لم يتم الدفع',
            self::PAID => 'مدفوع',
            self::PARTIALLY_PAID => 'دفع جزئي',
            self::OVERDUE => 'متأخر',
            self::REFUNDED => 'مسترجع',
            self::PENDING => 'قيد المعالجة',
            self::PENDING_VERIFICATION => 'في انتظار التحقق',
            self::REJECTED => 'مرفوض',
            self::CANCELLED => 'تم الإلغاء',
            self::TRIAL => 'فترة تجربة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UNPAID => 'warning',
            self::PAID => 'success',
            self::PARTIALLY_PAID => 'info',
            self::OVERDUE => 'danger',
            self::REFUNDED => 'dark',
            self::PENDING => 'secondary',
            self::PENDING_VERIFICATION => 'info',
            self::REJECTED, self::CANCELLED, self::TRIAL => 'danger',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ],
            self::cases()
        );
    }
}

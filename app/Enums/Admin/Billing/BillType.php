<?php

namespace App\Enums\Admin\Billing;

enum BillType: string
{
    case NEW_SUBSCRIPTION = 'new_subscription'; // اشتراك جديد
    case RENEWAL = 'renewal';          // تجديد
    case LICENSE_PURCHASE = 'license_purchase';  // شراء تراخيص
    case BILLING_CYCLE_CHANGE = 'billing_cycle_change';  //  تحويل دورة الفوترة
    case UPDATE_SUBSCRIPTION = 'update_subscription';

    public function label(): string
    {
        return match ($this) {
            self::NEW_SUBSCRIPTION => 'اشتراك جديد',
            self::RENEWAL => 'تجديد الاشتراك',
            self::LICENSE_PURCHASE => 'شراء مستخدمين إضافيين',
            self::BILLING_CYCLE_CHANGE => 'تحويل دورة الفوترة',
            self::UPDATE_SUBSCRIPTION => 'تعديل اشتراك',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW_SUBSCRIPTION => 'primary',
            self::RENEWAL => 'success',
            self::LICENSE_PURCHASE => 'info',
            self::BILLING_CYCLE_CHANGE => 'warning',
            self::UPDATE_SUBSCRIPTION => 'info',
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

<?php

namespace App\Enums\Admin\Billing;

enum BillType: string
{
    case NEW_SUBSCRIPTION       = 'new_subscription'; // اشتراك جديد
    case RENEWAL                = 'renewal';          // تجديد
    case LICENSE_PURCHASE       = 'license_purchase';  // شراء تراخيص
    case BILLING_CYCLE_CHANGE   = 'billing_cycle_change';  //  تحويل دورة الفوترة
    case NEW_DOMAIN             = 'new_domain';
    case TRANSFER_DOMAIN        = 'transfer_domain';
    case RENEWAL_DOMAIN         = 'renewal_domain';
    case UPDATE_SUBSCRIPTION    = 'update_subscription';

    public function label(): string
    {
        return match ($this) {
            self::NEW_SUBSCRIPTION      => 'اشتراك جديد',
            self::RENEWAL               => 'تجديد الاشتراك',
            self::LICENSE_PURCHASE      => 'شراء تراخيص إضافية',
            self::BILLING_CYCLE_CHANGE  => 'تحويل دورة الفوترة',
            self::NEW_DOMAIN            => 'دومين جديد',
            self::TRANSFER_DOMAIN       => 'نقل دومين',
            self::RENEWAL_DOMAIN        => 'تجديد دومين',
            self::UPDATE_SUBSCRIPTION   => 'تعديل اشتراك',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW_SUBSCRIPTION      => 'primary',
            self::RENEWAL               => 'success',
            self::LICENSE_PURCHASE      => 'info',
            self::BILLING_CYCLE_CHANGE  => 'warning',
            self::NEW_DOMAIN            => 'success',
            self::TRANSFER_DOMAIN       => 'warning',
            self::RENEWAL_DOMAIN        => 'info',
            self::UPDATE_SUBSCRIPTION   => 'info',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ],
            self::cases()
        );
    }
}

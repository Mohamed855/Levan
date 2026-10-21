<?php

declare(strict_types=1);

namespace App\Enums\Admin\Coupons;

enum CouponDiscountType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'مبلغ ثابت',
            self::PERCENTAGE => 'نسبة مئوية',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FIXED => 'primary',
            self::PERCENTAGE => 'success',
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

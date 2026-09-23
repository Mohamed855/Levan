<?php

namespace App\Enums\Admin\Coupons;

enum CouponApplicableTo: string
{
    case LICENSES = 'licenses';
    case PACKAGES = 'packages';
    case BOTH     = 'both';

    public function label(): string
    {
        return match ($this) {
            self::LICENSES => 'التراخيص فقط',
            self::PACKAGES => 'الباقات فقط',
            self::BOTH     => 'التراخيص والباقات',
        };
    }


    public function color(): string
    {
        return match ($this) {
            self::LICENSES => 'info',
            self::PACKAGES => 'warning',
            self::BOTH     => 'primary',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => [
                'value'       => $case->value,
                'label'       => $case->label(),
                'color'       => $case->color(),
            ],
            self::cases()
        );
    }


    public function description(): string
    {
        return match ($this) {
            self::PACKAGES  => 'ينطبق على الباقات فقط',
            self::LICENSES  => 'ينطبق على التراخيص الاضافية فقط',
            self::BOTH      => 'ينطبق على الباقات والتراخيص',
        };
    }
}

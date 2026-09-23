<?php

namespace App\Enums\Admin\Subscriptions;

enum SubscriptionStatus: string
{
    case ACTIVE         = 'active';
    case CANCELLED      = 'cancelled';
    case EXPIRED        = 'expired';
    case SUSPENDED      = 'suspended';

    case TRIAL = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE        => 'نشط',
            self::CANCELLED     => 'ملغي',
            self::EXPIRED       => 'منتهي',
            self::SUSPENDED     => 'معلق',

            self::TRIAL     => 'فترة تجربة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE            => 'success',
            self::CANCELLED         => 'secondary',
            self::EXPIRED           => 'danger',
            self::SUSPENDED         => 'dark',

            self::TRIAL         => 'dark',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $t) => ['id' => $t->value, 'name' => $t->label()],
            self::cases()
        );
    }
}

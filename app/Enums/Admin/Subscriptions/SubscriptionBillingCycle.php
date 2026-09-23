<?php

namespace App\Enums\Admin\Subscriptions;


enum SubscriptionBillingCycle: string
{
    case MONTHLY    = 'monthly';
    case YEARLY     = 'yearly';


    public function label(): string
    {
        return match ($this) {
            self::MONTHLY   => 'شهري',
            self::YEARLY    => 'سنوي',
        };
    }
    public function color(): string
    {
        return match ($this) {
            self::MONTHLY      => 'info',

            self::YEARLY         => 'dark',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $t) => ['id' => $t->value, 'name' => $t->label()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}

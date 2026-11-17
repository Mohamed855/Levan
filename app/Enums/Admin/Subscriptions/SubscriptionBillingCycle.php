<?php

declare(strict_types=1);

namespace App\Enums\Admin\Subscriptions;

enum SubscriptionBillingCycle: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'شهرياً',
            self::YEARLY => 'سنوياً',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MONTHLY => 'info',
            self::YEARLY => 'warning',
        };
    }

    public function durationMonths(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::YEARLY => 12,
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $t) => ['id' => $t->value, 'name' => $t->label()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

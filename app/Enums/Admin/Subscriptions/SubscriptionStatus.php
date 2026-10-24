<?php

declare(strict_types=1);

namespace App\Enums\Admin\Subscriptions;

enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case TRIAL = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد المراجعة',
            self::ACTIVE => 'نشط',
            self::CANCELLED => 'ملغي',
            self::EXPIRED => 'منتهي',
            self::SUSPENDED => 'معلق',
            self::TRIAL => 'فترة تجربة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'primary',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::CANCELLED => 'danger',
            self::EXPIRED => 'secondary',
            self::TRIAL => 'dark',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $t) => ['id' => $t->value, 'name' => $t->label()],
            self::cases()
        );
    }

    /** @return array<string, list<string>> */
    public static function allowedTransitions(): array
    {
        return [
            self::PENDING->value => [self::ACTIVE->value, self::CANCELLED->value],
            self::TRIAL->value => [self::ACTIVE->value, self::SUSPENDED->value, self::CANCELLED->value],
            self::ACTIVE->value => [self::SUSPENDED->value, self::CANCELLED->value],
            self::SUSPENDED->value => [self::ACTIVE->value, self::CANCELLED->value],
            self::EXPIRED->value => [self::ACTIVE->value],
            self::CANCELLED->value => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::allowedTransitions()[$from] ?? [], true);
    }
}

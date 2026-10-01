<?php

declare(strict_types=1);

namespace App\Enums\Admin\Plans;

enum PlanLimitsMode: string
{
    case LIMITED   = 'limited';
    case UNLIMITED = 'unlimited';

    public function label(): string
    {
        return match ($this) {
            self::LIMITED   => 'محدود',
            self::UNLIMITED => 'غير محدود',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LIMITED   => 'info',
            self::UNLIMITED => 'primary',
        };
    }

    public function isUnlimited(): bool
    {
        return $this === self::UNLIMITED;
    }

    public static function options(): array
    {
        return array_map(
            fn(self $t) => ['value' => $t->value, 'label' => $t->label(), 'color' => $t->color()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}

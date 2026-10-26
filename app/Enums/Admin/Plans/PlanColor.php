<?php

declare(strict_types=1);

namespace App\Enums\Admin\Plans;

enum PlanColor: string
{
    case PURPLE = 'purple';
    case BLUE = 'blue';
    case GREEN = 'green';
    case ORANGE = 'orange';
    case RED = 'red';
    case GRAY = 'gray';

    public function label(): string
    {
        return match ($this) {
            self::PURPLE => 'بنفسجي',
            self::BLUE => 'أزرق',
            self::GREEN => 'أخضر',
            self::ORANGE => 'برتقالي',
            self::RED => 'أحمر',
            self::GRAY => 'رمادي',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::PURPLE => '#7367f0',
            self::BLUE => '#00cfe8',
            self::GREEN => '#28c76f',
            self::ORANGE => '#ff9f43',
            self::RED => '#ea5455',
            self::GRAY => '#82868b',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PURPLE => 'bg-primary',
            self::BLUE => 'bg-info',
            self::GREEN => 'bg-success',
            self::ORANGE => 'bg-warning',
            self::RED => 'bg-danger',
            self::GRAY => 'bg-secondary',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'hex' => $t->hex(),
                'badgeClass' => $t->badgeClass(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

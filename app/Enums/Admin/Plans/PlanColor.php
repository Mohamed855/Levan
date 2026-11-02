<?php

declare(strict_types=1);

namespace App\Enums\Admin\Plans;

enum PlanColor: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case BLUE = 'blue';
    case GREEN = 'green';
    case ORANGE = 'orange';
    case RED = 'red';
    case GRAY = 'gray';
    case DARK = 'dark';
    case PURPLE = 'purple'; // Backward-compatibility alias

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY, self::PURPLE => 'اللون الأساسي',
            self::SECONDARY => 'اللون الثانوي',
            self::BLUE => 'أزرق',
            self::GREEN => 'أخضر',
            self::ORANGE => 'برتقالي',
            self::RED => 'أحمر',
            self::GRAY => 'رمادي',
            self::DARK => 'داكن',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::PRIMARY, self::PURPLE => '#1D4F44',
            self::SECONDARY => '#BF874A',
            self::BLUE => '#0284c7',
            self::GREEN => '#10b981',
            self::ORANGE => '#f59e0b',
            self::RED => '#ef4444',
            self::GRAY => '#6c757d',
            self::DARK => '#0f172a',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PRIMARY, self::PURPLE => 'bg-primary',
            self::SECONDARY => 'bg-gold',
            self::BLUE => 'bg-info',
            self::GREEN => 'bg-success',
            self::ORANGE => 'bg-warning',
            self::RED => 'bg-danger',
            self::GRAY => 'bg-secondary',
            self::DARK => 'bg-dark',
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
            self::selectableCases()
        );
    }

    public static function selectableCases(): array
    {
        return [
            self::PRIMARY,
            self::SECONDARY,
            self::BLUE,
            self::GREEN,
            self::ORANGE,
            self::RED,
            self::GRAY,
            self::DARK,
        ];
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

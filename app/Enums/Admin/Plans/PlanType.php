<?php

declare(strict_types=1);

namespace App\Enums\Admin\Plans;

enum PlanType: string
{
    case BASIC = 'basic';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'أساسي',
            self::PROFESSIONAL => 'احترافي',
            self::ENTERPRISE => 'مؤسسي',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BASIC => 'secondary',
            self::PROFESSIONAL => 'primary',
            self::ENTERPRISE => 'success',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $t) => ['value' => $t->value, 'label' => $t->label(), 'color' => $t->color()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

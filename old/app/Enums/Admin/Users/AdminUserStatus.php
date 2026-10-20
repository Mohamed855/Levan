<?php

namespace App\Enums\Admin\Users;

enum AdminUserStatus: string
{
    case INACTIVE = 'inactive';
    case ACTIVE = 'active';

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'غير نشط',
            self::ACTIVE => 'نشط',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
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

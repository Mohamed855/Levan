<?php

declare(strict_types=1);

namespace App\Enums\Admin\Support;

enum SupportPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'منخفضة',
            self::MEDIUM => 'متوسطة',
            self::HIGH => 'مرتفعة',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Collections;

enum PaymentType: string
{
    case SETTLEMENT = 'settlement';
    case WITHOUT_SETTLEMENT = 'without_settlement';

    public function label(): string
    {
        return match ($this) {
            self::SETTLEMENT => 'سداد بتسوية',
            self::WITHOUT_SETTLEMENT => 'سداد بدون تسوية',
        };
    }
}

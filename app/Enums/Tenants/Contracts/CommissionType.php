<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Contracts;

enum CommissionType: string
{
    case PROCEDURE = 'procedure';
    case DURATION = 'duration';
    case DELINQUENCY = 'delinquency';

    public function label(): string
    {
        return match ($this) {
            self::PROCEDURE => 'حسب الإجراء',
            self::DURATION => 'حسب المدة',
            self::DELINQUENCY => 'حسب مدة التعثر',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum DistributionMethod: string
{
    case BALANCED_AMOUNT = 'balanced_amount';
    case BALANCED_COUNT = 'balanced_count';
    case HYBRID_BALANCED = 'hybrid_balanced';

    public function label(): string
    {
        return match ($this) {
            self::BALANCED_AMOUNT => 'توزيع متوازن (مبلغ)', self::BALANCED_COUNT => 'توزيع متوازن (عدد)', self::HYBRID_BALANCED => 'توزيع مختلط'
        };
    }
}

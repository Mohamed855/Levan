<?php

declare(strict_types=1);

namespace App\Enums\Admin\Support;

enum SupportProblemType: string
{
    case TECHNICAL = 'technical';
    case BILLING = 'billing';
    case ACCOUNT = 'account';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TECHNICAL => 'مشكلة تقنية',
            self::BILLING => 'مشكلة في الفواتير',
            self::ACCOUNT => 'مشكلة في الحساب',
            self::OTHER => 'أخرى',
        };
    }
}

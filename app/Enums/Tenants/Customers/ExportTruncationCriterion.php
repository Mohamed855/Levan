<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum ExportTruncationCriterion: string
{
    case CREATED_DESC = 'created_desc';
    case CREATED_ASC = 'created_asc';
    case DEBT_DESC = 'debt_desc';
    case DEBT_ASC = 'debt_asc';
    case PAID_DESC = 'paid_desc';
    case PAID_ASC = 'paid_asc';

    public function label(): string
    {
        return match ($this) {
            self::CREATED_DESC => 'الأحدث إنشاءً',
            self::CREATED_ASC => 'الأقدم إنشاءً',
            self::DEBT_DESC => 'الأعلى مديونية',
            self::DEBT_ASC => 'الأقل مديونية',
            self::PAID_DESC => 'الأعلى دفعًا',
            self::PAID_ASC => 'الأقل دفعًا',
        };
    }
}

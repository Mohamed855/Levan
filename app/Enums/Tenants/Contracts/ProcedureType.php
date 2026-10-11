<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Contracts;

enum ProcedureType: string
{
    case FRIENDLY = 'friendly';
    case LEGAL = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::FRIENDLY => 'ودي',
            self::LEGAL => 'قانوني',
        };
    }
}

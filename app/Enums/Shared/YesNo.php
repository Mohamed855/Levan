<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum YesNo: string
{
    case NO = 'no';
    case YES = 'yes';

    public function label(): string
    {
        return match ($this) {
            self::NO => 'لا',
            self::YES => 'نعم',
        };
    }
}

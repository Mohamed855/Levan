<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum AllScope: string
{
    case ALL = 'all';

    public function label(): string
    {
        return 'الكل';
    }
}

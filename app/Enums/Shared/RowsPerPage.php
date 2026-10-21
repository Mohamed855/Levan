<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum RowsPerPage: int
{
    case TEN = 10;
    case TWENTY = 20;
    case FIFTY = 50;

    public function label(): string
    {
        return (string) $this->value;
    }
}

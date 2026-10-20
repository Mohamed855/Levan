<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\City;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralCityOptionsGateway
{
    /** @return array<string, string> */
    public function optionsByName(): array
    {
        return Tenancy::central(
            fn (): array => City::query()
                ->orderBy('name')
                ->pluck('name', 'name')
                ->all()
        );
    }
}

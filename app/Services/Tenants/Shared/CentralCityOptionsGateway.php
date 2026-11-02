<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\City;
use App\Models\Admin\District;
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

    /** @return array<string, string> */
    public function districtsByCityName(?string $cityName): array
    {
        if (! $cityName) {
            return [];
        }

        return Tenancy::central(
            fn (): array => District::query()
                ->whereHas('city', fn ($query) => $query->where('name', $cityName))
                ->orderBy('name')
                ->pluck('name', 'name')
                ->all()
        );
    }

    /** @return array<int, array{id: string, text: string}> */
    public function districtsListForSelect2(?string $cityName): array
    {
        $districts = $this->districtsByCityName($cityName);
        $list = [];

        foreach ($districts as $name) {
            $list[] = [
                'id' => $name,
                'text' => $name,
            ];
        }

        return $list;
    }
}

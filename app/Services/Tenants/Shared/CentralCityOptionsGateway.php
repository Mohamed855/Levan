<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\City;
use App\Models\Admin\District;
use App\Models\Admin\Region;
use App\Models\Tenants\Settings\Settings;
use Illuminate\Support\Facades\Schema;
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
    public function allRegionOptionsByName(): array
    {
        $dbOptions = Tenancy::central(
            function (): array {
                if (! Schema::hasTable('regions')) {
                    return [];
                }

                return Region::query()
                    ->orderBy('name')
                    ->pluck('name', 'name')
                    ->all();
            }
        );

        if (! empty($dbOptions)) {
            return $dbOptions;
        }

        $defaultRegions = [
            'المنطقة الشرقية',
            'منطقة الباحة',
            'منطقة الجوف',
            'منطقة الحدود الشمالية',
            'منطقة الرياض',
            'منطقة القصيم',
            'منطقة المدينة المنورة',
            'منطقة تبوك',
            'منطقة جازان',
            'منطقة حائل',
            'منطقة عسير',
            'منطقة مكة المكرمة',
            'منطقة نجران',
        ];

        return array_combine($defaultRegions, $defaultRegions);
    }

    /** @return array<string, string> */
    public function regionOptionsByName(): array
    {
        $all = $this->allRegionOptionsByName();

        if (tenancy()->initialized) {
            try {
                $configured = Settings::query()->value('operational_regions');
                if (is_string($configured)) {
                    $configured = json_decode($configured, true);
                }

                if (is_array($configured) && ! empty($configured)) {
                    $filtered = [];
                    foreach ($configured as $regionName) {
                        if (is_string($regionName) && trim($regionName) !== '') {
                            $name = trim($regionName);
                            $filtered[$name] = $name;
                        }
                    }

                    if (! empty($filtered)) {
                        return $filtered;
                    }
                }
            } catch (\Throwable) {
                // Fall back to all if settings cannot be queried
            }
        }

        return $all;
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

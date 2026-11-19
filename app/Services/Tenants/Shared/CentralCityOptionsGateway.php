<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\City;
use App\Models\Admin\District;
use App\Models\Admin\Region;
use App\Models\Tenants\Settings\Settings;
use Illuminate\Database\Eloquent\Collection;
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
        if (! tenancy()->initialized) {
            return [];
        }

        try {
            $settings = Settings::first();
            $configured = $settings?->operational_regions;
            if (is_string($configured)) {
                $configured = json_decode($configured, true);
            }

            if (! is_array($configured) || empty($configured)) {
                return [];
            }

            $all = $this->allRegionOptionsByName();
            $filtered = [];

            foreach ($configured as $regionName) {
                if (is_string($regionName) && trim($regionName) !== '') {
                    $name = trim($regionName);
                    $filtered[$name] = $all[$name] ?? $name;
                }
            }

            return $filtered;
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string, string> */
    public function districtsByCityName(?string $cityName): array
    {
        return $this->districtsByCityNames($cityName ? [$cityName] : []);
    }

    /**
     * @param  array<int, string>|string|null  $cityNames
     * @return array<string, string>
     */
    public function districtsByCityNames(array|string|null $cityNames): array
    {
        $names = is_array($cityNames) ? $cityNames : ($cityNames ? [$cityNames] : []);
        $names = array_values(array_filter(array_map('trim', array_map('strval', $names)), static fn (string $v): bool => $v !== ''));

        if (empty($names)) {
            return [];
        }

        return Tenancy::central(
            fn (): array => District::query()
                ->whereHas('city', fn ($query) => $query->whereIn('name', $names))
                ->orderBy('name')
                ->pluck('name', 'name')
                ->all()
        );
    }

    /**
     * @param  array<int, string>|string|null  $cityNames
     * @return array<int, array{id: string, text: string, city: string}>
     */
    public function districtsListForSelect2(array|string|null $cityNames): array
    {
        $names = is_array($cityNames) ? $cityNames : ($cityNames ? [$cityNames] : []);
        $names = array_values(array_filter(array_map('trim', array_map('strval', $names)), static fn (string $v): bool => $v !== ''));

        if (empty($names)) {
            return [];
        }

        /** @var Collection<int, District> $districts */
        $districts = Tenancy::central(
            fn () => District::query()
                ->whereHas('city', fn ($query) => $query->whereIn('name', $names))
                ->with('city:id,name')
                ->orderBy('name')
                ->get(['id', 'city_id', 'name'])
        );

        $citiesByDistrict = [];
        foreach ($districts as $district) {
            $cityName = $district->city?->name ?? '';
            if (! isset($citiesByDistrict[$district->name])) {
                $citiesByDistrict[$district->name] = [];
            }
            if ($cityName !== '' && ! in_array($cityName, $citiesByDistrict[$district->name], true)) {
                $citiesByDistrict[$district->name][] = $cityName;
            }
        }

        $list = [];
        foreach ($citiesByDistrict as $name => $cityList) {
            $list[] = [
                'id' => $name,
                'text' => $name,
                'city' => implode(',', $cityList),
            ];
        }

        return $list;
    }
}

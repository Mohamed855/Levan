<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\Settings\Settings;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $allRegions = app(CentralCityOptionsGateway::class)->allRegionOptionsByName();
        $defaultRegions = ! empty($allRegions) ? array_values($allRegions) : [
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

        $settings = Settings::query()->first();
        $image = $settings?->image;
        $isDefaultOrDemo = empty($image) || in_array($image, ['default-office.png', 'image/demo-office.jpg'], true);

        Settings::updateOrCreate(
            ['id' => $settings?->getKey() ?? 1],
            [
                'image' => $isDefaultOrDemo ? null : $image,
                'office_name' => 'لوت LOT',
                'logo_text' => 'لوت LOT',
                'operational_regions' => $defaultRegions,
            ]
        );
    }
}

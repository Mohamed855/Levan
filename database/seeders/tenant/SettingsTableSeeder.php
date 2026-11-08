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

        Settings::updateOrCreate(
            ['id' => Settings::query()->value('id') ?? 1],
            [
                'image' => 'default-office.png',
                'office_name' => 'لوت LOT',
                'logo_text' => 'لوت LOT',
                'operational_regions' => $defaultRegions,
            ]
        );
    }
}

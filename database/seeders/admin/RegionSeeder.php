<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use App\Models\Admin\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            'منطقة الرياض',
            'منطقة مكة المكرمة',
            'منطقة المدينة المنورة',
            'المنطقة الشرقية',
            'منطقة القصيم',
            'منطقة عسير',
            'منطقة تبوك',
            'منطقة حائل',
            'منطقة الحدود الشمالية',
            'منطقة جازان',
            'منطقة نجران',
            'منطقة الباحة',
            'منطقة الجوف',
        ];

        foreach ($regions as $name) {
            Region::firstOrCreate(['name' => $name]);
        }
    }
}

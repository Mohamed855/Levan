<?php

namespace Database\Seeders\admin;

use App\Models\Admin\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'الرياض', 'جدة', 'مكة المكرمة', 'المدينة المنورة', 'الدمام',
            'الخبر', 'الطائف', 'تبوك', 'أبها', 'بريدة', 'حائل', 'جازان',
            'نجران', 'الجبيل', 'ينبع',
        ] as $name) {
            City::firstOrCreate(['name' => $name]);
        }
    }
}

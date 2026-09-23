<?php

namespace Database\Seeders\admin;

use App\Models\Admin\Settings\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // نظّف الجدول أولاً
        Settings::truncate();

        Settings::create([
            'image'       => 'default-office.png',
            'office_name' => 'ليفـــان LEVAN',
            'logo_text'   => 'ليفـــان LEVAN',
        ]);
    }
}

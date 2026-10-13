<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\Settings\Settings;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        Settings::updateOrCreate(
            ['id' => Settings::query()->value('id') ?? 1],
            [
                'image' => 'default-office.png',
                'office_name' => 'لوت LOT',
                'logo_text' => 'لوت LOT',
            ]
        );
    }
}

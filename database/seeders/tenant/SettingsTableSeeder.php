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
                'office_name' => 'ليفـــان LEVAN',
                'logo_text' => 'ليفـــان LEVAN',
                'compare_effort' => true,
                'daily_tasks' => true,
                'Overdue_tasks' => true,
            ]
        );
    }
}

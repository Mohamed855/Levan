<?php

namespace Database\Seeders\admin;

use App\Models\Admin\Settings\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Settings::updateOrCreate(
            ['id' => Settings::query()->value('id') ?? 1],
            [
                'image'       => 'default-office.png',
                'office_name' => 'ليفـــان LEVAN',
                'logo_text'   => 'ليفـــان LEVAN',
            ]
        );
    }
}

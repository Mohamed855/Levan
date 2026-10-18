<?php

namespace Database\Seeders\admin;

use Database\Seeders\admin\adminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            CitySeeder::class,
            PermissionsRolesSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            VersionSeeder::class,
            TrialPlanSeeder::class,
            TestTenantSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders\admin;

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
            PublicPlanSeeder::class,
            TrialPlanSeeder::class,
            TestTenantSeeder::class,
        ]);
    }
}

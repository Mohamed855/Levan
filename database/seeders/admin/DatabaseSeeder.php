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
            FaqSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                TestTenantSeeder::class,
            ]);
        }
    }
}

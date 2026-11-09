<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            CitySeeder::class,
            DistrictSeeder::class,
            RegionSeeder::class,
            PermissionsRolesSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            PublicPlanSeeder::class,
            TrialPlanSeeder::class,
            FaqSeeder::class,
        ]);

        if (config('seeding.test_seeders_enabled', false)) {
            $this->call([
                TestTenantSeeder::class,
            ]);
        }
    }
}

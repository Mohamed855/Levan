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
            AuctionTemplateSamplesPermissionsSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            PublicPlanSeeder::class,
            TrialPlanSeeder::class,
            TestTenantSeeder::class,
        ]);
    }
}

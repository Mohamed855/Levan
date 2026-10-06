<?php

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsRolesSeeder::class,
            SettingsTableSeeder::class,
            GeneralSettingsSeeder::class,
            CompanyPortfolioSeeder::class,
            UserSeeder::class,
            CustomerDataSeeder::class,
            AuctionsAndPropertiesSeeder::class,
        ]);
    }
}

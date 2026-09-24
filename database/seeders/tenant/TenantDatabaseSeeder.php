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
            BranchSeeder::class,
            CustomerStatusSeeder::class,
            ContactResultSeeder::class,
            GeneralSettingsSeeder::class,
            UserSeeder::class,
            CompanyPortfolioSeeder::class,
            CustomerDataSeeder::class,
        ]);
    }
}

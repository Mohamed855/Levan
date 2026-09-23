<?php

namespace Database\Seeders\tenant;


use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            // SettingsTableSeeder::class,
            // PermissionsRolesSeeder::class,
            // UserSeeder::class,

            // SettingsCountrySeeder::class,
            // ContactResultSeeder::class,
            // CustomerStatusSeeder::class,
        ]);
    }
}

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
            PermissionsRolesSeeder::class,
            SettingsTableSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
        ]);
    }
}

<?php

declare(strict_types=1);

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
            UserSeeder::class,
            AuctionsAndPropertiesSeeder::class,
        ]);
    }
}

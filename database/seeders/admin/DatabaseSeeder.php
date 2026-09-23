<?php

namespace Database\Seeders\admin;

use Database\Seeders\admin\adminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            PermissionsRolesSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            VersionSeeder::class,
            TestTenantSeeder::class,
        ]);
    }
}

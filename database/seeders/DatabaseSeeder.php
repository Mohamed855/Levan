<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            WebSiteSeeder::class,
            AccessControlSeeder::class,
            AdminUserSeeder::class,
            DashboardDemoSeeder::class,
        ]);
    }
}

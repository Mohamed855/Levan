<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call([
            WebSiteSeeder::class,
            AccessControlSeeder::class,
            AdminUserSeeder::class,
            DashboardDemoSeeder::class,
        ]);
    }
}

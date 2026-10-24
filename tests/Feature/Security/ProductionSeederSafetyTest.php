<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DashboardDemoSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductionSeederSafetyTest extends TestCase
{
    public function test_central_database_seeder_does_not_connect_to_a_database_in_production(): void
    {
        $this->app->instance('env', 'production');
        DB::shouldReceive('connection')->never();

        app(DatabaseSeeder::class)->run();
        app(AdminUserSeeder::class)->run();
        app(DashboardDemoSeeder::class)->run();

        $this->addToAssertionCount(1);
    }

    public function test_central_database_seeder_also_refuses_staging(): void
    {
        $this->app->instance('env', 'staging');
        DB::shouldReceive('connection')->never();

        app(DatabaseSeeder::class)->run();
        app(AdminUserSeeder::class)->run();
        app(DashboardDemoSeeder::class)->run();

        $this->addToAssertionCount(1);
    }
}

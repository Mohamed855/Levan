<?php

namespace Database\Seeders\admin;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Version\Version;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        $version = Version::first();

        Tenant::firstOrCreate(
            ['id' => 'test'],
            [
                'db_database' => env('TEST_TENANT_DATABASE', 'etmam_levan_test'),
                'db_username' => env('DB_USERNAME', 'admin'),
                'db_password' => env('DB_PASSWORD', 'admin'),
                'db_host'     => env('DB_HOST', '127.0.0.1'),
                'db_port'     => env('DB_PORT', '3306'),
                'folderName'  => 'test',
                'company_name' => 'مستخدم النسخة الثانية',
                'commercial_number' => '2222222222',
                'version_id'  => $version?->id ?? 1,
                'access_type' => 'path',
                'custom_domain_active' => 0,
                'api_token'   => Str::random(60),
                'data'        => [],
            ]
        );
    }
}

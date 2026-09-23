<?php

namespace Database\Seeders\admin;

use App\Enums\Admin\Version\VersionStage;
use App\Models\Admin\Version\Version;
use Illuminate\Database\Seeder;

class VersionSeeder extends Seeder
{
    public function run(): void
    {
        Version::firstOrCreate(
            [
                'version'       => 'v1.0.0',
                'file_path'     => '/home/levan/public_html/releases/v1.0.0-beta',
                'stage'         => VersionStage::STABLE,
                'is_active'     => true,
                'created_by'    => 1,
            ]
        );
    }
}

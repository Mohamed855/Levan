<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\Branch\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'الفرع الرئيسي',
                'phone' => '0500000000',
                'city' => 'الرياض',
                'status' => 'active',
            ]
        );
    }
}

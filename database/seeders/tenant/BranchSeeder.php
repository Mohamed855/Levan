<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\UserMangment\Team\Team;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'الفرع الرئيسي - الرياض',
                'phone' => '0500000001',
                'city' => 'الرياض',
                'status' => 'active',
            ]
        );

        $jeddahBranch = Branch::updateOrCreate(
            ['code' => 'JED'],
            [
                'name' => 'فرع جدة',
                'phone' => '0500000002',
                'city' => 'جدة',
                'status' => 'active',
            ]
        );

        Team::updateOrCreate(
            ['name' => 'فريق التحصيل الميداني'],
            [
                'branch_id' => $mainBranch->id,
                'description' => 'فريق متابعة وتحصيل الحسابات الميدانية',
                'status' => 'active',
            ]
        );

        Team::updateOrCreate(
            ['name' => 'فريق التحصيل الهاتفي'],
            [
                'branch_id' => $mainBranch->id,
                'description' => 'فريق متابعة الاتصالات والتحصيل المكتبي',
                'status' => 'active',
            ]
        );
    }
}

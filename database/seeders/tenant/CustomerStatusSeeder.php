<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use Illuminate\Database\Seeder;

class CustomerStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'على رأس العمل', 'status' => 'active'],
            ['name' => 'بدون عمل', 'status' => 'active'],
            ['name' => 'متقاعد', 'status' => 'active'],
            ['name' => 'متوفي', 'status' => 'inactive'],
            ['name' => 'مسجون', 'status' => 'inactive'],
            ['name' => 'خروج نهائي', 'status' => 'inactive'],
        ];

        foreach ($statuses as $status) {
            SettingsCustomerStatus::updateOrCreate(
                ['name' => $status['name']],
                ['status' => $status['status']]
            );
        }
    }
}

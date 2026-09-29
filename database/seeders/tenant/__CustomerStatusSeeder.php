<?php

namespace Database\Seeders\tenant;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class __CustomerStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'على رأس العمل',
            'بدون عمل',
            'متقاعد',
            'متوفي',
            'مسجون',
            'خروج نهائي',
        ];

        $now = Carbon::now();

        foreach ($statuses as $status) {
            DB::table('settings_customer_status')->updateOrInsert(
                ['name' => $status],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}

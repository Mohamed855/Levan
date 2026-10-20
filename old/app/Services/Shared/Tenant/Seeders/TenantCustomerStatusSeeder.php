<?php

namespace App\Services\shared\Tenant\Seeders;

use App\Contracts\Shared\TenantSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TenantCustomerStatusSeeder implements TenantSeeder
{
    public function seed(): void
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
                [
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
        }
    }
}

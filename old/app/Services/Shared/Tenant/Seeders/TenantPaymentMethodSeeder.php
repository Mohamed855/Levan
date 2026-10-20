<?php

namespace App\Services\shared\Tenant\Seeders;

use App\Contracts\Shared\TenantSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TenantPaymentMethodSeeder implements TenantSeeder
{
    public function seed(): void
    {
        $statuses = [
            'سداد عن طريق الفرع',
            'تمارا',
            'تحويل بنكي',
        ];

        $now = Carbon::now();

        foreach ($statuses as $status) {
            DB::table('settings_payment_methods')->updateOrInsert(
                ['name' => $status],
                [
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
        }
    }
}

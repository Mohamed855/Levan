<?php

namespace App\Services\shared\Tenant\Seeders;

use App\Contracts\Shared\TenantSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TenantContactMethodSeeder implements TenantSeeder
{
    public function seed(): void
    {
        $statuses = [
            'WhatsApp',
            'اتصال هاتفي',
        ];

        $now = Carbon::now();

        foreach ($statuses as $status) {
            DB::table('settings_contact_methods')->updateOrInsert(
                ['name' => $status],
                [
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
        }
    }
}

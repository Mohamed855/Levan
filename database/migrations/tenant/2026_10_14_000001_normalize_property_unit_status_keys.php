<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'متاح' => 'available',
            'مؤجر' => 'rented',
            'مباع' => 'sold',
        ] as $displayLabel => $persistedValue) {
            DB::table('property_units')
                ->where('status', $displayLabel)
                ->update(['status' => $persistedValue]);
        }
    }

    public function down(): void
    {
        // This is a data-normalization migration: rolling it back must not reintroduce invalid persisted keys.
    }
};

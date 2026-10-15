<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'فيلا' => 'villa',
            'شقة' => 'apartment',
            'محل' => 'shop',
            'حديقة' => 'garden',
        ] as $legacy => $normalized) {
            DB::table('property_units')->where('unit_type', $legacy)->update(['unit_type' => $normalized]);
        }

        foreach ([
            'سكني' => 'residential',
            'تجاري' => 'commercial',
        ] as $legacy => $normalized) {
            DB::table('property_units')->where('current_use', $legacy)->update(['current_use' => $normalized]);
        }

        DB::table('property_units')
            ->whereNotNull('unit_type')
            ->whereNotIn('unit_type', ['villa', 'apartment', 'shop', 'garden', 'other'])
            ->update(['unit_type' => 'other']);

        DB::table('property_units')
            ->whereNotNull('current_use')
            ->whereNotIn('current_use', ['residential', 'commercial', 'other'])
            ->update(['current_use' => 'other']);
    }

    public function down(): void
    {
        // Persisted English keys intentionally remain stable on rollback.
    }
};

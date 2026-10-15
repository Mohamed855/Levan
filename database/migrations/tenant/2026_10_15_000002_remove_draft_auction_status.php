<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('auctions')
            ->where('status', 'draft')
            ->update([
                'status' => 'in_preparation',
                'is_active' => false,
            ]);
    }

    public function down(): void
    {
        // A persisted draft is no longer a valid auction business state.
    }
};

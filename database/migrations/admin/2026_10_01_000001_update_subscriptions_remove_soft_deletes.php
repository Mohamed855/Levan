<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permanently delete any currently soft-deleted subscriptions and clean their cascaded relations
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'deleted_at')) {
            $trashedIds = DB::table('subscriptions')
                ->whereNotNull('deleted_at')
                ->pluck('id');

            if ($trashedIds->isNotEmpty()) {
                // Delete cascaded relations first if needed
                DB::table('license_seats')->whereIn('subscription_id', $trashedIds)->delete();
                DB::table('subscription_periods')->whereIn('subscription_id', $trashedIds)->delete();
                DB::table('bills')->whereIn('subscription_id', $trashedIds)->update(['subscription_id' => null]);
                DB::table('subscriptions')->whereIn('id', $trashedIds)->delete();
            }

            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && ! Schema::hasColumn('subscriptions', 'deleted_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};

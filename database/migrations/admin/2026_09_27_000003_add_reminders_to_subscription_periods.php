<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('subscription_periods', function (Blueprint $table) {
            $table->timestamp('reminded_at_30')->nullable()->after('is_current');
            $table->timestamp('reminded_at_7')->nullable()->after('reminded_at_30');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('subscription_periods', function (Blueprint $table) {
            $table->dropColumn(['reminded_at_30', 'reminded_at_7']);
        });
    }
};

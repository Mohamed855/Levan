<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('settings', function (Blueprint $table) {
            $table->boolean('tickets_enabled')->default(true)->after('etmam_enabled');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('settings', function (Blueprint $table) {
            $table->dropColumn('tickets_enabled');
        });
    }
};

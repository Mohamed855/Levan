<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('report_phone', 30)->nullable();
            $table->string('report_unified_phone', 30)->nullable();
            $table->string('report_social')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->dropColumn(['report_phone', 'report_unified_phone', 'report_social']));
    }
};

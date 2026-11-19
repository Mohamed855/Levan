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
            $table->string('unified_number', 10)->nullable()->after('office_name');
            $table->string('fal_license', 50)->nullable()->after('unified_number');
            $table->string('representative_name', 255)->nullable()->after('fal_license');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['unified_number', 'fal_license', 'representative_name']);
        });
    }
};

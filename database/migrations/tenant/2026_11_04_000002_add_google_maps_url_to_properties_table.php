<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            if (! Schema::hasColumn('properties', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('parcel_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            if (Schema::hasColumn('properties', 'google_maps_url')) {
                $table->dropColumn('google_maps_url');
            }
        });
    }
};

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
            $table->unsignedSmallInteger('age')->nullable()->default(null)->after('area');
            $table->string('legal_status', 50)->default('clear')->after('ownership_type');
            $table->string('legal_status_notes', 255)->nullable()->after('legal_status');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn(['age', 'legal_status', 'legal_status_notes']);
        });
    }
};

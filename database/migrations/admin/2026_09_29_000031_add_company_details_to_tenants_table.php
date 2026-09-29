<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tax_number')->nullable()->after('commercial_number');
            $table->foreignId('city_id')->nullable()->after('tax_number')->constrained('cities')->nullOnDelete();
            $table->string('address')->nullable()->after('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn(['tax_number', 'city_id', 'address']);
        });
    }
};

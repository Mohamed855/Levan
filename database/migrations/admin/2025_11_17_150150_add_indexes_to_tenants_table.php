<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('id');
            $table->index('db_database');
            $table->index(['id', 'db_database']); // Composite index
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['id']);
            $table->dropIndex(['db_database']);
            $table->dropIndex(['id', 'db_database']);
        });
    }
};

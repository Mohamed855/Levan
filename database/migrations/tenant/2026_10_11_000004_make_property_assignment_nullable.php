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
            $table->dropForeign(['assignment_id']);
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedBigInteger('assignment_id')->nullable()->change();
            $table->foreign('assignment_id')->references('id')->on('assignments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropForeign(['assignment_id']);
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedBigInteger('assignment_id')->nullable(false)->change();
            $table->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
        });
    }
};

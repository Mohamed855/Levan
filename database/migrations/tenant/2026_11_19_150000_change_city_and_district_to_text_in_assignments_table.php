<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->text('city')->nullable()->change();
            $table->text('district')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->string('city', 255)->nullable()->change();
            $table->string('district', 255)->nullable()->change();
        });
    }
};

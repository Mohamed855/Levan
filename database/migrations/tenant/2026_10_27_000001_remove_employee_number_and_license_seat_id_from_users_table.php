<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'employee_number')) {
                try {
                    $table->dropUnique(['employee_number']);
                } catch (\Throwable) {
                    // Ignore if unique index was named differently or already dropped
                }
                $table->dropColumn('employee_number');
            }

            if (Schema::hasColumn('users', 'license_seat_id')) {
                $table->dropColumn('license_seat_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'employee_number')) {
                $table->string('employee_number')->nullable()->unique();
            }

            if (! Schema::hasColumn('users', 'license_seat_id')) {
                $table->uuid('license_seat_id')->nullable();
            }
        });
    }
};

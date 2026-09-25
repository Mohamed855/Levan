<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('collection_process_sessions')->onDelete('cascade');
            $table->dateTime('entry_time');
            $table->dateTime('exit_time')->nullable();
            $table->enum('exit_type', ['manual', 'timeout', 'end_of_day'])->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->integer('activities_count')->default(0);
            $table->integer('follows_count')->default(0);
            $table->integer('full_payments_count')->default(0);
            $table->integer('partial_payments_count')->default(0);
            $table->timestamps();
            $table->index('session_id');
            $table->index('entry_time');
            $table->index('exit_time');
            $table->index(['session_id', 'exit_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_periods');
    }
};

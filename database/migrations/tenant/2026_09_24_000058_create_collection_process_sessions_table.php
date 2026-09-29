<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_process_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->dateTime('first_entry_time')->nullable();
            $table->dateTime('last_exit_time')->nullable();
            $table->integer('total_work_minutes')->default(0);
            $table->integer('total_activities')->default(0);
            $table->integer('total_follows')->default(0);
            $table->integer('total_full_payments')->default(0);
            $table->integer('total_partial_payments')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
            $table->index('date');
            $table->index('is_active');
            $table->index(['date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_process_sessions');
    }
};

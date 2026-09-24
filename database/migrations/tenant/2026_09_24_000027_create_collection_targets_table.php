<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->json('team_ids')->nullable();
            $table->enum('target_type', ['monthly', 'yearly', 'custom'])->default('monthly')->index();
            $table->integer('target_month')->nullable();
            $table->integer('target_year')->nullable();
            $table->date('custom_start_date')->nullable();
            $table->date('custom_end_date')->nullable();
            $table->decimal('target_amount', 15, 2);
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['branch_id', 'user_id']);
            $table->index(['target_type', 'target_year', 'target_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_targets');
    }
};

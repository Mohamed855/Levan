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
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('team_ids')->nullable();
            $table->string('target_type')->index();
            $table->unsignedTinyInteger('target_month')->nullable();
            $table->unsignedSmallInteger('target_year');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('target_amount', 14, 2);
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'target_year', 'target_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_targets');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->time('start_time')->nullable();
            $table->date('end_date');
            $table->time('end_time')->nullable();
            $table->enum('type', ['electronic', 'in_person', 'hybrid'])->default('electronic');
            $table->string('category')->default('lands');
            $table->string('city');
            $table->unsignedInteger('expected_properties_count')->default(1);
            $table->string('supervising_entity')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('platform_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedBigInteger('brochure_template_id')->nullable()->index();
            $table->unsignedBigInteger('billboard_template_id')->nullable()->index();
            $table->unsignedBigInteger('social_template_id')->nullable()->index();
            $table->enum('status', ['in_preparation', 'pending_approval', 'active', 'completed', 'suspended'])->default('in_preparation')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};

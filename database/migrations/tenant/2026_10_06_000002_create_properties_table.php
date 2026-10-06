<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->string('property_number')->index();
            $table->string('name');
            $table->string('type')->default('residential_land');
            $table->string('city');
            $table->string('district')->nullable();
            $table->decimal('area', 12, 2)->default(0);
            $table->string('deed_number')->nullable()->index();
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->enum('data_status', ['completed', 'needs_images', 'incomplete'])->default('incomplete')->index();
            $table->enum('status', ['available', 'assigned_to_auction', 'sold'])->default('available')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('main_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};

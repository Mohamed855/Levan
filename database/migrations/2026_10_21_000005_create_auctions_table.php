<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            // This id becomes a foreign key in each tenant database when tenant provisioning is enabled.
            $table->unsignedBigInteger('assignment_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->time('start_time')->nullable();
            $table->date('end_date');
            $table->time('end_time')->nullable();
            $table->string('type', 30)->default('electronic')->index();
            $table->string('category', 30)->default('lands')->index();
            $table->string('city', 100);
            $table->unsignedInteger('expected_properties_count')->default(1);
            $table->string('supervising_entity')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('platform_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('cover_image')->nullable();
            // Central template versions have no database FK across tenant boundaries.
            $table->json('template_versions')->nullable();
            $table->json('template_snapshot')->nullable();
            $table->json('template_bindings')->nullable();
            $table->string('status', 30)->default('in_preparation')->index();
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
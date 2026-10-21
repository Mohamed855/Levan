<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable()->unique();
            $table->string('plan_type', 50)->default('basic');
            $table->string('color', 30)->default('purple');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->string('limits_mode', 20)->default('limited');
            $table->unsignedInteger('included_users')->nullable();
            $table->unsignedInteger('properties_limit')->nullable();
            $table->unsignedInteger('storage_quota_gb')->nullable();
            $table->unsignedInteger('active_auctions_limit')->nullable();
            $table->boolean('domain_management_enabled')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('subscription_duration_type', 20)->default('limited');
            $table->string('subscription_billing_cycle', 20)->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('plans');
    }
};

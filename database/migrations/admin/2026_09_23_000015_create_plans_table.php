<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable()->unique();
            $table->string('plan_type', 50)->default('basic');
            $table->string('color', 30)->default('purple');
            $table->timestamp('available_from')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('limits_mode', 20)->default('limited');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('yearly_renewal_price', 10, 2)->default(0);
            $table->decimal('extra_user_license_price', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('domain_management_enabled')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('included_users')->nullable();
            $table->unsignedInteger('properties_limit')->nullable();
            $table->unsignedInteger('storage_quota_gb')->nullable();
            $table->unsignedInteger('active_auctions_limit')->nullable();
            $table->enum('subscription_duration_type', ['limited', 'unlimited'])->default('limited');
            $table->enum('subscription_billing_cycle', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->foreignId('created_by')->constrained('admin_users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

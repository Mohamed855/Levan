<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('customer_email');
            $table->string('customer_phone', 20);
            $table->string('company_name')->nullable();
            $table->string('commercial_number', 10)->nullable()->index();
            $table->string('tax_number', 15)->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('address')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('plain_password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->uuid('submission_token')->nullable()->unique();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('provisioning_status')->default('pending');
            $table->text('provisioning_error')->nullable();
            $table->timestamp('provisioning_started_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_requests');
    }
};

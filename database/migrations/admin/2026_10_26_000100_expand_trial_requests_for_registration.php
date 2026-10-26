<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('trial_requests', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->string('commercial_number', 10)->nullable()->index();
            $table->string('tax_number', 15)->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('address')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->uuid('submission_token')->nullable()->unique();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('provisioning_status')->default('pending');
            $table->text('provisioning_error')->nullable();
            $table->timestamp('provisioning_started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('trial_requests', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['company_name', 'commercial_number', 'tax_number', 'city_id', 'address', 'password_hash', 'email_verified_at', 'submission_token', 'tenant_id', 'subscription_id', 'provisioning_status', 'provisioning_error', 'provisioning_started_at']);
        });
    }
};

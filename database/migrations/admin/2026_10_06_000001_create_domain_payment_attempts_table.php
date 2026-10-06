<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('subscription_periods')->nullOnDelete();
            $table->string('operation', 32)->index();
            $table->string('domain', 255);
            $table->text('epp_code')->nullable();
            $table->unsignedSmallInteger('years')->default(1);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('status', 32)->default('pending')->index();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'domain', 'operation'], 'domain_payment_attempt_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_payment_attempts');
    }
};

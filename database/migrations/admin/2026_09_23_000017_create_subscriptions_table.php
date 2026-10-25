<?php

use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number')->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->enum('status', array_column(SubscriptionStatus::cases(), 'value'))->default(SubscriptionStatus::PENDING->value);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('used_users')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('next_payment_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('transfer_bank_attachment')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

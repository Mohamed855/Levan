<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('subscription_number')->unique();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('total_users')->default(1);
            $table->unsignedInteger('used_users')->default(1);
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('next_payment_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'ends_at']);
        });

        Schema::create('subscription_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('period_number');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->foreignId('plan_id_snapshot')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('plan_code_snapshot', 50);
            $table->string('plan_name_snapshot');
            $table->string('plan_type_snapshot', 50);
            $table->string('billing_cycle_snapshot', 20);
            $table->string('limits_mode_snapshot', 20)->default('limited');
            $table->json('limits_snapshot')->nullable();
            $table->json('features_snapshot')->nullable();
            $table->decimal('price_snapshot', 10, 2);
            $table->unsignedInteger('total_users_snapshot')->default(1);
            $table->unsignedInteger('used_users_snapshot')->default(1);
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscription_id', 'period_number']);
            $table->index(['subscription_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_periods');
        Schema::dropIfExists('subscriptions');
    }
};

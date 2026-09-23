<?php

use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_periods', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('period_number');

            //  الاشتراك
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');

            // بداية ونهاية الفترة
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            // تخزين بعض القيمة للرجوع اليها
            $table->foreignId('plan_id_snapshot')->nullable()->constrained('plans')->nullOnDelete();
            $table->decimal('price_snapshot', 10, 2)->nullable();
            $table->unsignedInteger('total_users_snapshot')->default(0);
            $table->unsignedInteger('used_users_snapshot')->default(0);

            // حالة الفترة
            $table->enum('status', array_column(SubscriptionPeriodStatus::cases(), 'value'))->default(SubscriptionPeriodStatus::QUEUED->value);

            // هل هذه الفترة الحالية؟
            $table->boolean('is_current')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['subscription_id', 'status']);
            $table->index(['subscription_id', 'is_current']);
            $table->index('period_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_periods');
    }
};

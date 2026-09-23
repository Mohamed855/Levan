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

            // رقم الاشتراك
            $table->string('subscription_number')->unique();

            // بداية الاشتراك وحتكون ثابته في اول مرة يشترك فيها
            $table->timestamp('starts_at')->nullable();

            //  المستأجر
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // الباقة
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            // حالة الاشتراك
            $table->enum('status', array_column(SubscriptionStatus::cases(), 'value'))->default(SubscriptionStatus::SUSPENDED->value);

            // السعر
            $table->decimal('price', 10, 2);

            // عدد المستخدمين
            $table->unsignedInteger('total_users')->default(0); // عدد المستخدمين المتاحين في الاشتراك
            $table->unsignedInteger('used_users')->default(0);  // عدد المستخدمين الذين تم استخدامهم فعلياً

            // التجديد التلقائي
            $table->boolean('auto_renew')->default(true);

            // موعد الدفع القادم
            $table->timestamp('next_payment_date')->nullable();

            // الإلغاء
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

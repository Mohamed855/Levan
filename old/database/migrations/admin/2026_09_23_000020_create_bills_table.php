<?php

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('subscription_periods')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('original_amount', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();
            $table->string('coupon_type', 20)->nullable()->comment('percentage, fixed');
            $table->decimal('coupon_value', 10, 2)->nullable();
            $table->enum('type', array_column(BillType::cases(), 'value'))->default(BillType::NEW_SUBSCRIPTION->value);
            $table->enum('payment_method', array_column(PaymentMethod::cases(), 'value'))->nullable();
            $table->enum('status', array_column(BillStatus::cases(), 'value'))->default(BillStatus::PENDING->value);
            $table->timestamp('due_date')->nullable()->comment('تاريخ الاستحقاق');
            $table->timestamp('paid_at')->nullable()->comment('تاريخ الدفع الفعلي');
            $table->timestamp('cancelled_at')->nullable()->comment('تاريخ الإلغاء');
            $table->decimal('settlement_amount', 10, 2)->nullable()->default(0)->comment('مبلغ التسوية النهائي');
            $table->string('invoice_url')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->date('transfer_date')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('transfer_notes')->nullable();
            $table->text('reject_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['subscription_id', 'period_id'], 'idx_bill_sub_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};

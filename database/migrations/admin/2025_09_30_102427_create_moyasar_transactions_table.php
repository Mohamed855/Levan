<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('moyasar_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('payment_id')->unique()->index();

            $table->enum('transaction_type', [
                'payment',  // دفع جديد
                'refund',   // استرجاع
                'void',     // إلغاء
                'capture'   // التقاط (للدفعات المعلقة)
            ])->default('payment');

            $table->string('transactionable_type')->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();

            $table->index(['transactionable_type', 'transactionable_id'], 'trx_type_id_index');

            // المبلغ
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->decimal('fee', 10, 2)->nullable(); // رسوم ميسر

            // الحالة وطريقة الدفع
            $table->string('status')->index();
            // الحالات: initiated, authorized, paid, failed, refunded, voided, captured

            $table->string('payment_method')->nullable();
            // creditcard, mada, stcpay, applepay, samsungpay

            // الوصف
            $table->text('description')->nullable();
            $table->string('callback_url')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();

            $table->string('card_brand')->nullable(); // visa, mastercard, mada
            $table->string('card_last_four', 4)->nullable();

            // بيانات إضافية
            $table->json('metadata')->nullable(); // بيانات مخصصة
            $table->json('moyasar_response')->nullable(); // الرد الكامل من ميسر

            // في حالة الفشل
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();

            // التواريخ
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->index(['status', 'payment_method']);
            $table->index('customer_email');
            $table->index('paid_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('moyasar_transactions');
    }
};

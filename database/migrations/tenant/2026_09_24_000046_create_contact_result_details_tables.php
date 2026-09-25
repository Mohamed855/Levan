<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول تفاصيل السداد الكامل
        Schema::create('full_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->decimal('payment_amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_type', ['bank_transfer', 'branch', 'settlement', 'receipt']);
            $table->string('payment_receipt_path')->nullable();
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });

        // جدول تفاصيل السداد الجزئي
        Schema::create('partial_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->decimal('partial_payment_amount', 10, 2);
            $table->date('partial_payment_date');
            $table->enum('partial_payment_type', ['bank_transfer', 'branch', 'settlement', 'receipt']);
            $table->string('partial_payment_receipt_path')->nullable();
            $table->date('next_payment_date')->nullable();
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });

        // جدول تفاصيل وعد بالسداد
        Schema::create('promise_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->decimal('promise_amount', 10, 2);
            $table->date('promise_date');
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });

        // جدول تفاصيل معاودة الاتصال
        Schema::create('callback_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->dateTime('callback_date');
            $table->enum('callback_reason', ['client_busy', 'branch_review', 'branch_verification', 'other'])->default('client_busy');
            $table->string('other_reason')->nullable();
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });

        // جدول تفاصيل طلب المستندات
        Schema::create('document_request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->date('documents_date');
            $table->enum('documents_reason', ['verification', 'objection', 'other'])->nullable();
            $table->unsignedBigInteger('documents_reason_id')->nullable();
            $table->string('other_reason')->nullable();
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });

        // جدول تفاصيل الاعتراض
        Schema::create('objection_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->string('objection_reason', 255);
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });

        // جدول تفاصيل الاسترداد
        Schema::create('recovery_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->enum('recovery_reason', ['valid_print', 'has_license', 'star_report', 'traffic_decision', 'court_deed', 'other'])->default('valid_print');
            $table->string('recovery_document_path')->nullable();
            $table->string('other_reason')->nullable();
            $table->timestamps();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_details');
        Schema::dropIfExists('objection_details');
        Schema::dropIfExists('document_request_details');
        Schema::dropIfExists('callback_details');
        Schema::dropIfExists('promise_payment_details');
        Schema::dropIfExists('partial_payment_details');
        Schema::dropIfExists('full_payment_details');
    }
};

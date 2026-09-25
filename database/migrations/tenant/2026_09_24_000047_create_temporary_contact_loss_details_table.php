<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_contact_loss_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->enum('temporary_contact_reason', [
                'debt_amount_objection',
                'fraud_operation',
                'previous_complaint',
                'other'
            ])->comment('سبب عدم التواصل المؤقت');
            $table->text('temporary_contact_reason_other')->nullable()->comment('سبب آخر لعدم التواصل');
            $table->timestamps();

            $table->foreign('contact_result_id')
                  ->references('id')
                  ->on('customer_follows')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_contact_loss_details');
    }
};

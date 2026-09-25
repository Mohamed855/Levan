<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('full_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->decimal('payment_amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_type', ['bank_transfer', 'branch', 'settlement', 'receipt']);
            $table->string('payment_receipt_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('full_payment_details');
    }
};

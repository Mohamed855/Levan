<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partial_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->decimal('partial_payment_amount', 10, 2);
            $table->date('partial_payment_date');
            $table->enum('partial_payment_type', ['bank_transfer', 'branch', 'settlement', 'receipt']);
            $table->string('partial_payment_receipt_path')->nullable();
            $table->date('next_payment_date')->nullable();
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
        Schema::dropIfExists('partial_payment_details');
    }
};

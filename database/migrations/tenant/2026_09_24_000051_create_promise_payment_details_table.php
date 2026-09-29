<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promise_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->decimal('promise_amount', 10, 2);
            $table->date('promise_date');
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
        Schema::dropIfExists('promise_payment_details');
    }
};

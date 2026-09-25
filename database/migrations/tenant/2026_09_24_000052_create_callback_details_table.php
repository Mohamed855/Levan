<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callback_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->dateTime('callback_date');
            $table->enum('callback_reason', ['client_busy', 'branch_review', 'branch_verification', 'other'])->default('client_busy');
            $table->string('other_reason')->nullable();
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
        Schema::dropIfExists('callback_details');
    }
};

<?php

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
        Schema::create('customer_payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_follow_id')->nullable()->constrained('customer_follows')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('due_date');
            $table->decimal('due_amount', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'deleted_at'], 'customer_payment_plans_customer_deleted_index');
            $table->index(['created_by'], 'customer_payment_plans_created_by_index');
            $table->index(['due_date'], 'customer_payment_plans_due_date_index');
            $table->index(['created_at'], 'customer_payment_plans_created_at_index');
            $table->index('customer_follow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_plans');
    }
};

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
        Schema::create('customer_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('settlement_date');
            $table->decimal('settlement_amount', 15, 2);
            $table->string('settlement_number');
            $table->enum('settlement_type', [
                'cash_with_update',
                'cash_without_update',
                'purchase_debt_with_update',
                'purchase_debt_without_update'
            ]);
            $table->string('file_path')->nullable();
            $table->string('file_type', 10)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'deleted_at'], 'customer_settlements_customer_deleted_index');
            $table->index(['created_by'], 'customer_settlements_created_by_index');
            $table->index(['settlement_date'], 'customer_settlements_date_index');
            $table->index(['settlement_number'], 'customer_settlements_number_index');
            $table->index(['settlement_type'], 'customer_settlements_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_settlements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('payment_reconciliations')->onDelete('cascade');
            $table->string('account_number');
            $table->decimal('file_amount', 15, 2);
            $table->date('file_date');
            $table->integer('row_number');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('collection_id')->nullable()->constrained('collections')->onDelete('set null');
            $table->enum('match_status', ['matched','unmatched','discrepancy','error']);
            $table->decimal('system_amount', 15, 2)->nullable();
            $table->date('system_date')->nullable();
            $table->decimal('amount_difference', 15, 2)->nullable();
            $table->integer('date_difference_days')->nullable();
            $table->text('error_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('reconciliation_id');
            $table->index('account_number');
            $table->index('match_status');
            $table->index(['customer_id', 'collection_id']);
            $table->index('row_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();            
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('portfolio_id')->constrained('portfolios')->onDelete('cascade');
            $table->date('reconciliation_month');
            $table->string('file_path');            
            $table->integer('total_rows')->default(0);
            $table->integer('matched_count')->default(0);
            $table->integer('unmatched_count')->default(0);
            $table->integer('discrepancy_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('system_only_count')->default(0);            
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('matched_amount', 15, 2)->default(0);
            $table->decimal('unmatched_amount', 15, 2)->default(0);
            $table->decimal('discrepancy_amount', 15, 2)->default(0);            
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('processed_at')->nullable();            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');            
            $table->timestamps();
            $table->softDeletes();            
            $table->index(['company_id', 'portfolio_id']);
            $table->index('reconciliation_month');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
    }
};

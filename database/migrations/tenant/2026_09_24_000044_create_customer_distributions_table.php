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
        Schema::create('customer_distributions', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();
            $table->unsignedBigInteger('collector_id')->index('idx_distributions_collector');
            $table->integer('customers_count')->default(0);
            $table->decimal('total_debt', 15, 2)->default(0);
            $table->enum('distribution_method', [
                'balanced_amount',
                'balanced_count',
                'hybrid_balanced'
            ])->index('idx_distributions_method');
            $table->json('distribution_criteria')->nullable();
            $table->json('customer_ids')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collector_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('created_at', 'idx_distributions_created_at');
            $table->index(['collector_id', 'created_at'], 'idx_distributions_collector_date');
            $table->index(['distribution_method', 'created_at'], 'idx_distributions_method_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_distributions');
    }
};

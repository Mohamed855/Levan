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
        Schema::create('contract_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->enum('commission_type', ['procedure', 'duration', 'delinquency']);
            $table->enum('procedure_type', ['friendly', 'legal'])->nullable();
            $table->integer('duration_from')->nullable();
            $table->integer('duration_to')->nullable();
            $table->decimal('commission_rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_commissions');
    }
};

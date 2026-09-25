<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('portfolio_field_id')->constrained('portfolio_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'portfolio_field_id'], 'cfv_customer_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_field_values');
    }
};

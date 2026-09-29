<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_customer_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_status_id')->constrained('settings_customer_status')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'customer_status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_customer_status');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_type')->default('text');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['portfolio_id', 'field_name']);
            $table->index(['portfolio_id', 'field_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_fields');
    }
};

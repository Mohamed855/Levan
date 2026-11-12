<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('result_status', 20)->nullable()->index();
            $table->decimal('opening_price', 15, 2)->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->unsignedInteger('participants_count')->nullable();
            $table->unsignedInteger('bids_count')->nullable();
            $table->text('unsold_reason')->nullable();
            $table->json('board_data')->nullable();
            $table->string('buyer_name')->nullable();
            $table->text('result_notes')->nullable();
            $table->timestamps();

            $table->unique(['auction_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_properties');
    }
};

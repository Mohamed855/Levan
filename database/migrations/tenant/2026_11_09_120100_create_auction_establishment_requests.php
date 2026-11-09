<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_establishment_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->unique()->constrained('auctions')->cascadeOnDelete();
            $table->json('data');
            $table->timestamp('submitted_at');
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_number')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_establishment_requests');
    }
};
